HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";

include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inf.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/webinc/config.php";

$nodebase = "/runtime/hnap/SetPortForwardingSettings";
$node_info = $nodebase."/PortForwardingList/PortForwardingInfo";
$pfwd = "/nat/entry/portforward";
$pfwd_entry = $pfwd."/entry";

$result = "OK";

set("/runtime/hnap/dummy", "");
movc($pfwd, "/runtime/hnap/dummy"); //Remove the children nodes of /nat/entry/portforward
del("/runtime/hnap/dummy");

set($pfwd."/seqno", "1");
set($pfwd."/max", "24"); 
set($pfwd."/count", "0");

foreach($node_info)
{
	set($pfwd."/seqno", $InDeX+1);
	set($pfwd."/count", $InDeX);
	
	$enable = get("x", "Enabled");
	if ($enable == "true") { set($pfwd_entry.":".$InDeX."/enable", "1"); }
	else { set($pfwd_entry.":".$InDeX."/enable", "0"); }
	
	set($pfwd_entry.":".$InDeX."/uid", "PFWD-".$InDeX);
	
	$schedule = get("x", "ScheduleName");
	set($pfwd_entry.":".$InDeX."/schedule", XNODE_getscheduleuid($schedule));
	
	set($pfwd_entry.":".$InDeX."/inbfilter", "");
	
	$description = get("x", "PortForwardingDescription");
	set($pfwd_entry.":".$InDeX."/description", $description);
	
	set($pfwd_entry.":".$InDeX."/protocol", "");
	set($pfwd_entry.":".$InDeX."/protocolnum", "");
	
	set($pfwd_entry.":".$InDeX."/internal/inf", $LAN1);
	$ipv4addr = get("x", "LocalIPAddress");
	$mask = INF_getcurrmask($LAN1);
	$hostid = ipv4hostid($ipv4addr, $mask);
	set($pfwd_entry.":".$InDeX."/internal/hostid", $hostid);
	set($pfwd_entry.":".$InDeX."/internal/start", "");
	
	$tport_str = get("x", "TCPPorts");
	set($pfwd_entry.":".$InDeX."/tport_str", $tport_str);
	
	$uport_str = get("x", "UDPPorts");
	set($pfwd_entry.":".$InDeX."/uport_str", $uport_str);
	
	set($pfwd_entry.":".$InDeX."/external/start", "");
	set($pfwd_entry.":".$InDeX."/external/end", "");
	
	TRACE_debug("$enable=".$enable);
	TRACE_debug("$schedule=".$schedule);
	TRACE_debug("$description=".$description);
	TRACE_debug("$ipv4addr=".$ipv4addr);
	TRACE_debug("$mask=".$mask);
	TRACE_debug("$hostid=".$hostid);
	TRACE_debug("$tport_str=".$tport_str);
	TRACE_debug("$tport_str=".$uport_str);
}

if($result == "OK")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
	fwrite("a",$ShellPath, "service PFWD.NAT-1 restart > /dev/console\n");
	fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");
	set("/runtime/hnap/dev_status", "ERROR");
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
    <SetPortForwardingSettingsResponse xmlns="http://purenetworks.com/HNAP1/">
      <SetPortForwardingSettingsResult><?=$result?></SetPortForwardingSettingsResult>
    </SetPortForwardingSettingsResponse>
  </soap:Body>
</soap:Envelope>