HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<? 
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";
include "/htdocs/phplib/xnode.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/trace.php"; 

$rphyinf1 = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $WLAN1, 0);
$rphyinf2 = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $WLAN2, 0);
$phyinf1 = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1, 0);
$phyinf2 = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN2, 0);
$wifi1 = XNODE_getpathbytarget("/wifi", "entry", "uid", query($phyinf1."/wifi"), 0);
$wifi2 = XNODE_getpathbytarget("/wifi", "entry", "uid", query($phyinf2."/wifi"), 0);

$dev_pin = get("","/runtime/hnap/SetWiFiVerifyAlpha/WPS/DEV_PIN");
$reset = get("","/runtime/hnap/SetWiFiVerifyAlpha/WPS/ResetToUnconfigured");
$pin = get("","/runtime/hnap/SetWiFiVerifyAlpha/WPS/WPSPIN");
$pbc = get("","/runtime/hnap/SetWiFiVerifyAlpha/WPS/WPSPBC");

$result = "ERROR_ACTION";

fwrite("w",$ShellPath, "#!/bin/sh\n");
if ($pin != "")
{
	if($rphyinf1 != "") {set($rphyinf1."/media/wps/enrollee/pin",$pin);}
	if($rphyinf2 != "") {set($rphyinf2."/media/wps/enrollee/pin",$pin);}
	
	if($rphyinf1 != "" || $rphyinf2 != "") 
	{
		fwrite("a",$ShellPath, "event WPSPIN > /dev/console \n");
		$result = "SUCCESS";
	}
	else {fwrite("a",$ShellPath, "echo \"PIN:can't find correct path...\" > /dev/console");}
}
else if ($pbc == "1")
{
	fwrite("a",$ShellPath, "event WPSPBC.PUSH > /dev/console \n");	
	$result = "SUCCESS";
}
else if ($dev_pin != "")
{
	if($wifi1!="") {set($wifi1."/wps/pin",$dev_pin);}
	if($wifi2!="") {set($wifi2."/wps/pin",$dev_pin);}
	
	if($wifi1!="" || $wifi2!="")
	{
		fwrite("a",$ShellPath, "event DBSAVE > /dev/console \n");
		fwrite("a",$ShellPath, "service PHYINF.WIFI restart > /dev/console \n");
		$result = "SUCCESS";
	}
	else {fwrite("a",$ShellPath, "echo \"DEV_PIN:can't find correct path...\" > /dev/console");}
}
else if ($reset == "1")
{
	TRACE_info("Reset to Unconfigured!!!");
	$result = "SUCCESS";
	fwrite("a",$ShellPath, "event FRESET > /dev/console \n");
}
else
{
	fwrite("a",$ShellPath, "echo \"We got a error in setting, so we do nothing...\" > /dev/console");
}
?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
	xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"> 
	<soap:Body>
		<SetWiFiVerifyAlphaResponse xmlns="http://purenetworks.com/HNAP1/">
			<SetWPSSettingResult><?=$result?></SetWPSSettingResult>
		</SetWiFiVerifyAlphaResponse>
	</soap:Body>
</soap:Envelope>
