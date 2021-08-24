HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";
include "/htdocs/webinc/config.php";

$nodebase = "/runtime/hnap/SetIPv6SimpleSecurity/";
$result = "OK";

$simple_security = 0;
if (get("x", $nodebase."Status") == "Enable") 
{ 
	$simple_security = 1;
	set("/device/simple_security", "1"); 
}
else 
{
	set("/device/simple_security", "0"); 
}

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo \"[$0]-->IPv6 Simple Security\" > /dev/console\n");

if($result=="OK")
{	
	if($simple_security == "1")
	{
		fwrite("a",$ShellPath, "service FIREWALL6 restart > /dev/console\n");
		fwrite("a",$ShellPath, "service IP6TSMPSECURITY restart > /dev/console\n");	
	}
	else
	{
		fwrite("a",$ShellPath, "service IP6TSMPSECURITY stop > /dev/console\n");		
	}
	
	fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");
	set("/runtime/hnap/dev_status", "ERROR");
}
else
{
	fwrite("a",$ShellPath, "echo \"We got a error in setting, so we do nothing...\" > /dev/console");
}

?>
<soap:Envelope 
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
	<soap:Body>
		<SetIPv6SimpleSecurityResponse xmlns="http://purenetworks.com/HNAP1/">
			<SetIPv6SimpleSecurityResponseResult><?=$result?></SetIPv6SimpleSecurityResponseResult>
		</SetIPv6SimpleSecurityResponse>
	</soap:Body>
</soap:Envelope>