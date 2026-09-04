<?php
namespace App\Services;

final class EvidenceStorage
{
    public function driver(): string{return EVIDENCE_STORAGE_DRIVER;}
    public function objectKey(string $name):string
    {
        $name=ltrim($name,'/');$prefix=trim(EVIDENCE_OBJECT_PREFIX,'/');
        return str_starts_with($name,$prefix.'/')?$name:$prefix.'/'.basename($name);
    }
    public function putUploaded(string $temporaryPath,string $name,string $mime,string $checksum):bool
    {
        if($this->driver()==='local'){
            $directory=dirname(__DIR__,2).'/uploads/workplan/';if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))return false;
            if(!move_uploaded_file($temporaryPath,$directory.basename($name)))return false;
            $stored=hash_file('sha256',$directory.basename($name));return is_string($stored)&&hash_equals($checksum,$stored);
        }
        $body=file_get_contents($temporaryPath);if($body===false)return false;
        $headers=['content-type'=>$mime,'x-amz-meta-sha256'=>$checksum];
        [$status]=$this->request('PUT',$this->objectKey($name),$body,$headers);
        if($status<200||$status>=300)return false;
        [$headStatus,,$responseHeaders]=$this->request('HEAD',$this->objectKey($name),'',[]);
        return $headStatus>=200&&$headStatus<300&&hash_equals($checksum,strtolower((string)($responseHeaders['x-amz-meta-sha256']??'')));
    }
    public function delete(string $name):bool
    {
        if($name==='')return true;
        if($this->driver()==='local'){$path=dirname(__DIR__,2).'/uploads/workplan/'.basename($name);return !is_file($path)||@unlink($path);}
        [$status]=$this->request('DELETE',$this->objectKey($name),'',[]);return in_array($status,[200,202,204,404],true);
    }
    public function url(string $name):string
    {
        if($name==='')return '';
        if($this->driver()==='local')return 'uploads/workplan/'.rawurlencode(basename($name));
        if(EVIDENCE_PUBLIC_BASE_URL!=='')return EVIDENCE_PUBLIC_BASE_URL.'/'.implode('/',array_map('rawurlencode',explode('/',$this->objectKey($name))));
        return $this->presignedGet($this->objectKey($name));
    }
    private function request(string $method,string $key,string $body,array $extra):array
    {
        if(!function_exists('curl_init'))throw new \RuntimeException('PHP cURL is required for S3 evidence storage.');
        $time=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));$amzDate=$time->format('Ymd\THis\Z');$date=$time->format('Ymd');$payloadHash=hash('sha256',$body);
        [$url,$host,$uri]=$this->target($key);$headers=array_merge(['host'=>$host,'x-amz-content-sha256'=>$payloadHash,'x-amz-date'=>$amzDate],$extra);ksort($headers);
        $canonicalHeaders='';foreach($headers as $name=>$value)$canonicalHeaders.=strtolower($name).':'.trim((string)$value)."\n";$signedHeaders=implode(';',array_keys($headers));
        $canonical=$method."\n".$uri."\n\n".$canonicalHeaders."\n".$signedHeaders."\n".$payloadHash;$scope=$date.'/'.EVIDENCE_S3_REGION.'/s3/aws4_request';$stringToSign="AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n".hash('sha256',$canonical);
        $signature=hash_hmac('sha256',$stringToSign,$this->signingKey($date));$authorization='AWS4-HMAC-SHA256 Credential='.EVIDENCE_S3_ACCESS_KEY.'/'.$scope.', SignedHeaders='.$signedHeaders.', Signature='.$signature;
        $curlHeaders=['Authorization: '.$authorization];foreach($headers as $name=>$value)$curlHeaders[]=$name.': '.$value;
        $responseHeaders=[];$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$curlHeaders,CURLOPT_HEADERFUNCTION=>static function($ch,$line)use(&$responseHeaders){$parts=explode(':',$line,2);if(count($parts)===2)$responseHeaders[strtolower(trim($parts[0]))]=trim($parts[1]);return strlen($line);}]);if($method==='PUT')curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
        $response=(string)curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);if($status===0)throw new \RuntimeException('S3 request failed: '.$error);return[$status,$response,$responseHeaders];
    }
    private function presignedGet(string $key):string
    {
        $time=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));$amzDate=$time->format('Ymd\THis\Z');$date=$time->format('Ymd');[$url,$host,$uri]=$this->target($key);$scope=$date.'/'.EVIDENCE_S3_REGION.'/s3/aws4_request';
        $query=['X-Amz-Algorithm'=>'AWS4-HMAC-SHA256','X-Amz-Credential'=>EVIDENCE_S3_ACCESS_KEY.'/'.$scope,'X-Amz-Date'=>$amzDate,'X-Amz-Expires'=>(string)EVIDENCE_SIGNED_URL_TTL,'X-Amz-SignedHeaders'=>'host'];ksort($query);$canonicalQuery=http_build_query($query,'','&',PHP_QUERY_RFC3986);$canonical="GET\n{$uri}\n{$canonicalQuery}\nhost:{$host}\n\nhost\nUNSIGNED-PAYLOAD";$stringToSign="AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n".hash('sha256',$canonical);$query['X-Amz-Signature']=hash_hmac('sha256',$stringToSign,$this->signingKey($date));return$url.'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986);
    }
    private function target(string $key):array
    {
        $endpoint=rtrim(EVIDENCE_S3_ENDPOINT,'/');$parts=parse_url($endpoint);if(!$parts||empty($parts['host']))throw new \RuntimeException('Invalid S3 endpoint.');$scheme=$parts['scheme']??'https';$basePath=rtrim($parts['path']??'','/');$encoded=implode('/',array_map('rawurlencode',explode('/',$key)));
        if(EVIDENCE_S3_PATH_STYLE){$host=$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');$uri=$basePath.'/'.rawurlencode(EVIDENCE_S3_BUCKET).'/'.$encoded;}
        else{$host=EVIDENCE_S3_BUCKET.'.'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');$uri=$basePath.'/'.$encoded;}
        return[$scheme.'://'.$host.$uri,$host,$uri];
    }
    private function signingKey(string $date):string{$kDate=hash_hmac('sha256',$date,'AWS4'.EVIDENCE_S3_SECRET_KEY,true);$kRegion=hash_hmac('sha256',EVIDENCE_S3_REGION,$kDate,true);$kService=hash_hmac('sha256','s3',$kRegion,true);return hash_hmac('sha256','aws4_request',$kService,true);}
}
