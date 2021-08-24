HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";

include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inf.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/webinc/config.php";

$nodebase = "/runtime/hnap/SetIPv4FirewallSettings";
$node_rule = $nodebase."/IPv4FirewallRuleLists/IPv4FirewallRule";

$result = "OK";

//Clear the all the rules in the /acl/firewall
$i = get("x", "/acl/firewall/entry#");
while($i > 0)
{
	del("/acl/firewall/entry");
	$i--;
}

/*
Ref: /etc/services/IPTFIREWALL.php
For Black list, the /acl/firewall/policy is ACCEPT and the /acl/firewall/entry/policy is DROP then the IP table would drop the setting IP or accept them.
For White list, the /acl/firewall/policy is DROP and the /acl/firewall/entry/policy is ACCEPT then the IP table would accept the setting IP or drop them.
*/
if(get("", $nodebase."/IPv4_FirewallStatus")=="Enable_BlackList")		set("/acl/firewall/policy", "ACCEPT");
else if(get("", $nodebase."/IPv4_FirewallStatus")=="Enable_WhiteList")	set("/acl/firewall/policy", "DROP");
else																	set("/acl/firewall/policy", "DISABLE");

foreach($node_rule)
{
	if($InDeX > get("", "/acl/firewall/max")) {break;}
	
	set("/acl/firewall/seqno", $InDeX+1);
	set("/acl/firewall/count", $InDeX);
	
	set("/acl/firewall/entry:".$InDeX."/description", get("", "Name"));
	
	if(get("x", "Status") == "Enable")	{set("/acl/firewall/entry:".$InDeX."/enable", "1");}
	else								{set("/acl/firewall/entry:".$InDeX."/enable", "0");}
	
	set("/acl/firewall/entry:".$InDeX."/schedule", XNODE_getscheduleuid(get("", "Schedule")));
	
	if(get("", "SrcInterface")=="WAN")	{set("/acl/firewall/entry:".$InDeX."/src/inf", "WAN-1");}
	else 								{set("/acl/firewall/entry:".$InDeX."/src/inf", "LAN-1");}
	$SrcIPv4AddressRangeStart = get("", "SrcIPv4AddressRangeStart");
	$SrcIPv4AddressRangeEnd = get("", "SrcIPv4AddressRangeEnd");
	if(ipv4hostid($SrcIPv4AddressRangeStart, "0") > ipv4hostid($SrcIPv4AddressRangeEnd, "0") && $SrcIPv4AddressRangeEnd != "")
	{
		$SrcIPv4AddressRangeStart = get("", "SrcIPv4AddressRangeEnd");
		$SrcIPv4AddressRangeEnd = get("", "SrcIPv4AddressRangeStart");
	}
	set("/acl/firewall/entry:".$InDeX."/src/host/start",	$SrcIPv4AddressRangeStart);
	set("/acl/firewall/entry:".$InDeX."/src/host/end",		$SrcIPv4AddressRangeEnd);

	if(get("", "DestInterface")=="WAN")	{set("/acl/firewall/entry:".$InDeX."/dst/inf", "WAN-1");}
	else 								{set("/acl/firewall/entry:".$InDeX."/dst/inf", "LAN-1");}
	$DestIPv4AddressRangeStart = get("", "DestIPv4AddressRangeStart");
	$DestIPv4AddressRangeEnd = get("", "DestIPv4AddressRangeEnd");
	if(ipv4hostid($DestIPv4AddressRangeStart, "0") > ipv4hostid($DestIPv4AddressRangeEnd, "0") && $DestIPv4AddressRangeEnd != "")
	{
		$DestIPv4AddressRangeStart = get("", "DestIPv4AddressRangeEnd");
		$DestIPv4AddressRangeEnd = get("", "DestIPv4AddressRangeStart");
	}
	$PortRangeStart = get("", "PortRangeStart");
	$PortRangeEnd = get("", "PortRangeEnd");
	if($PortRangeStart > $PortRangeEnd && $PortRangeEnd != "")
	{
		$PortRangeStart = get("", "PortRangeEnd");
		$PortRangeEnd = get("", "PortRangeStart");
	}
	set("/acl/firewall/entry:".$InDeX."/dst/host/start",	$DestIPv4AddressRangeStart);
	set("/acl/firewall/entry:".$InDeX."/dst/host/end",		$DestIPv4AddressRangeEnd);
	set("/acl/firewall/entry:".$InDeX."/dst/port/start",	$PortRangeStart);
	set("/acl/firewall/entry:".$InDeX."/dst/port/end",		$PortRangeEnd);
	
	if(get("x", "Protocol")=="TCP")		{set("/acl/firewall/entry:".$InDeX."/protocol", "TCP");}
	else if(get("x", "Protocol")=="UDP"){set("/acl/firewall/entry:".$InDeX."/protocol", "UDP");}
	else 								{set("/acl/firewall/entry:".$InDeX."/protocol", "TCP+UDP");}
	
	if(get("", $nodebase."/IPv4_FirewallStatus")=="Enable_BlackList")		set("/acl/firewall/entry:".$InDeX."/policy", "DROP");
	else if(get("", $nodebase."/IPv4_FirewallStatus")=="Enable_WhiteList")	set("/acl/firewall/entry:".$InDeX."/policy", "ACCEPT");
	
	// Check the UID of this entry, it should not be empty, and must be unique.  Refer FIREWALL6 fatlady. 
	set("/acl/firewall/entry:".$InDeX."/uid",	"FWL-".get("", "/acl/firewall/seqno"));
}

if($result == "OK")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
	fwrite("a",$ShellPath, "service FIREWALL restart > /dev/console\n");
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
    <SetIPv4FirewallSettingsResponse xmlns="http://purenetworks.com/HNAP1/">
      <SetIPv4FirewallSettingsResult><?=$result?></SetIPv4FirewallSettingsResult>
    </SetIPv4FirewallSettingsResponse>
  </soap:Body>
</soap:Envelope>
