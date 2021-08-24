HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/trace.php"; 
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/encrypt.php";

fwrite("w",$ShellPath, "#!/bin/sh\n");
$result="OK";

$hnap_action="SetMultipleActions_DeviceSettings";
$nodebase="/runtime/hnap/SetMultipleActions/SetDeviceSettings/";
if(exist($nodebase)==1)
{	
	TRACE_debug("SetMultipleActions.php: execute ".$hnap_action);
	include "etc/templates/hnap/SetMultipleActions_DeviceSettings.php";
	if($result!="OK")
	{
		TRACE_error($hnap_action." is not OK ret=".$result); 
	}
}

$hnap_action="SetMultipleActions_SetWanSettings";
$nodebase="/runtime/hnap/SetMultipleActions/SetWanSettings/";
if(exist($nodebase)==1)
{
	TRACE_debug("SetMultipleActions.php: execute ".$hnap_action);
	include "etc/templates/hnap/SetMultipleActions_SetWanSettings.php";
	if($result!="OK")
	{
		TRACE_error($hnap_action." is not OK ret=".$result);
	}
}

$hnap_action="SetMultipleActions_SmartconnectSettings";
foreach("/runtime/hnap/SetMultipleActions/SetSmartconnectSettings")
{
	$nodebase="/runtime/hnap/SetMultipleActions/SetSmartconnectSettings:".$InDeX."/";
	if(exist($nodebase)==1)
	{
		TRACE_debug("SetMultipleActions.php: execute ".$hnap_action);
		include "etc/templates/hnap/SetMultipleActions_SmartconnectSettings.php";
		if($result!="OK")
		{
			TRACE_error($hnap_action." is not OK ret=".$result);
		}
	}
}

$hnap_action="SetMultipleActions_WLanRadioSettings";
foreach("/runtime/hnap/SetMultipleActions/SetWLanRadioSettings")
{
	$nodebase="/runtime/hnap/SetMultipleActions/SetWLanRadioSettings:".$InDeX."/";
	if(exist($nodebase)==1)
	{
		TRACE_debug("SetMultipleActions.php: execute ".$hnap_action);
		include "etc/templates/hnap/SetMultipleActions_WLanRadioSettings.php";
		if($result!="OK")
		{
			TRACE_error($hnap_action." is not OK ret=".$result);
		}
	}
}

$hnap_action="SetMultipleActions_WLanRadioSecurity";
foreach("/runtime/hnap/SetMultipleActions/SetWLanRadioSecurity")
{
	$nodebase="/runtime/hnap/SetMultipleActions/SetWLanRadioSecurity:".$InDeX."/";
	if(exist($nodebase)==1)
	{
		TRACE_debug("SetMultipleActions.php: execute ".$hnap_action);
		include "etc/templates/hnap/SetMultipleActions_WLanRadioSecurity.php";
		if($result!="OK")
		{
			TRACE_error($hnap_action." is not OK ret=".$result);
		}
	}
}

$WirelessMode = query("/runtime/hnap/SetMultipleActions/SetOperationMode/CurrentOPMode");
if($WirelessMode=="WirelessRepeaterExtender")
	set("/device/wirelessmode", "manual");
else if($WirelessMode=="WirelessBridge")
	set("/device/wirelessmode", "client");
else
	set("/device/wirelessmode", "wps");

//==add for repeater and client mode
if($WirelessMode=="WirelessRepeaterExtender" || $WirelessMode=="WirelessBridge")
{
	$nodebase="/runtime/hnap/SetMultipleActions/SetAPClientSettings:".$InDeX."/";
	include "etc/templates/hnap/SetMultipleActions_SetAPClientSettings.php";
	TRACE_error("nodebase=".$nodebase); 
	if($result!="OK")
	{
		TRACE_error("SetAPClientSettings is not OK ret=".$result); 
	}
}

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo \"[$0]-->multiple setting\" > /dev/console\n");

if($result=="OK")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
	fwrite("a",$ShellPath, "service DEVICE.ACCOUNT restart > /dev/console\n");
	fwrite("a",$ShellPath, "service WAN restart > /dev/console\n");
	fwrite("a",$ShellPath, "service SMARTCONNECT restart > /dev/console\n");
	fwrite("a",$ShellPath, "service ".$SRVC_WLAN." restart > /dev/console\n");
	fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");
	set("/runtime/hnap/dev_status", "ERROR");
	//change to reboot ,we need restart much time
	$result="REBOOT";
}
else
{
	fwrite("a",$ShellPath, "echo \"We got a error in setting, so we do nothing...\" > /dev/console");
}

?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><SetMultipleActionsResponse xmlns="http://purenetworks.com/HNAP1/"><SetMultipleActionsResult><?=$result?></SetMultipleActionsResult></SetMultipleActionsResponse></soap:Body></soap:Envelope>
