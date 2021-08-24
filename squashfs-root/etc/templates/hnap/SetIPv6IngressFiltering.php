HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";
include "/htdocs/webinc/config.php";

$nodebase = "/runtime/hnap/SetIPv6IngressFiltering/";
$result = "OK";

$Ingress_Filtering = 0;
if (get("x", $nodebase."Status") == "Enable") 
{ 
	$Ingress_Filtering = 1;
	set("/device/ingress_filtering", "1"); 
}
else 
{
	set("/device/ingress_filtering", "0"); 
}

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo \"[$0]-->IPv6 Ingress Filtering\" > /dev/console\n");

if($result=="OK")
{	
	if($simple_security == "1")
	{
		fwrite("a",$ShellPath, "service IP6TINGRESSFILTER restart > /dev/console\n");	
	}
	else if ($Ingress_Filtering == "1")
	{
		fwrite("a",$ShellPath, "service FIREWALL6 restart > /dev/console\n");
		fwrite("a",$ShellPath, "service IP6TSMPSECURITY restart > /dev/console\n");
		//fwrite("a",$ShellPath, "service IP6TINGRESSFILTER stop > /dev/console\n");		
	}
	else
	{
		fwrite("a",$ShellPath, "service IP6TINGRESSFILTER stop > /dev/console\n");		
	}
	fwrite("a",$ShellPath, "event DBSAVE\n");
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
		<SetIPv6IngressFilteringResponse xmlns="http://purenetworks.com/HNAP1/">
			<SetIPv6IngressFilteringResponseResult><?=$result?></SetIPv6IngressFilteringResponseResult>
		</SetIPv6IngressFilteringResponse>
	</soap:Body>
</soap:Envelope>