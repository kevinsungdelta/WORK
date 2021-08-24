HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inet.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/encrypt.php";
$path_inf_wan1 = XNODE_getpathbytarget("", "inf", "uid", $WAN1, 0);

$nodebase="/runtime/hnap/SetDynamicDNSSettings/";
$rlt="OK";
$DDNS4_Enabled=get("x", $nodebase."Enabled");
$DDNS4_ServerAddress=get("x", $nodebase."ServerAddress");
$DDNS4_Hostname=get("x", $nodebase."Hostname");
$DDNS4_Username=get("x", $nodebase."Username");
$DDNS4_Password=get("x", $nodebase."Password");
$DDNS4_Password = AES_Decrypt128($DDNS4_Password);
$DDNS4_Timeout=get("x", $nodebase."Timeout");

if($DDNS4_Enabled == "true")	set($path_inf_wan1."/ddns4", "DDNS4-1");
else							set($path_inf_wan1."/ddns4", "");

set("/ddns4/count",	"1");
set("/ddns4/entry:1/uid",		"DDNS4-1");
/*
	Hans says DDNS server is only remained dyndns.com, others are manual input by users.
	However our router ddnsd only mapping some of specific DDNS server address. It should be modified later.
	provider	=>	Server Address (Web Show)
	DYNDNS			dyndns.com
	DLINK			dlinkddns.com
	DLINK.COM.CN	dlinkddns.com.cn
	CYBERGATE		cybergate.planex.co.jp
	IOBB			iobb.net
*/
if($DDNS4_ServerAddress=="dyndns.com")					{$DDNS4_ServerAddress="DYNDNS";}
else if($DDNS4_ServerAddress=="dlinkddns.com")			{$DDNS4_ServerAddress="DLINK";}
else if($DDNS4_ServerAddress=="dlinkddns.com.cn")		{$DDNS4_ServerAddress="DLINK.COM.CN";}
else if($DDNS4_ServerAddress=="cybergate.planex.co.jp")	{$DDNS4_ServerAddress="CYBERGATE";}
else if($DDNS4_ServerAddress=="iobb.net")				{$DDNS4_ServerAddress="IOBB";}
else if(isdomain($DDNS4_ServerAddress)!=1)				{$rlt="ERROR";} // Manual Input

if($rlt=="OK")
{
	set("/ddns4/entry:1/provider",	$DDNS4_ServerAddress);
	set("/ddns4/entry:1/hostname",	$DDNS4_Hostname);
	set("/ddns4/entry:1/username",	$DDNS4_Username);
	set("/ddns4/entry:1/password",	$DDNS4_Password);
	set("/ddns4/entry:1/interval",	$DDNS4_Timeout*60);
}

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo \"[$0]-->Dynamic DNS Change\" > /dev/console\n");
if($rlt=="OK")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
	fwrite("a",$ShellPath, "service DDNS4.WAN-1 restart > /dev/console\n");
	fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");	
	set("/runtime/hnap/dev_status", "ERROR");
}
else
{
	fwrite("a",$ShellPath, "echo \"We got a error in setting, so we do nothing...\" > /dev/console\n");
}
?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<SetDynamicDNSSettingsResponse xmlns="http://purenetworks.com/HNAP1/">
<SetDynamicDNSSettingsResult><?=$rlt?></SetDynamicDNSSettingsResult>
</SetDynamicDNSSettingsResponse>
</soap:Body>
</soap:Envelope>
