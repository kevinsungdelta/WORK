HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inf.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/trace.php"; 

$result="OK";
/* those data have complete checked by client side, we just simply check at here */
$ipaddress = get("","/runtime/hnap/SetNetworkSettings/IPAddress");
$subnetmask = get("","/runtime/hnap/SetNetworkSettings/SubnetMask");
$DeviceName = get("","/runtime/hnap/SetNetworkSettings/DeviceName");
$local_domain_name = get("","/runtime/hnap/SetNetworkSettings/LocalDomainName");
$start = get("","/runtime/hnap/SetNetworkSettings/IPRangeStart");
$end = get("","/runtime/hnap/SetNetworkSettings/IPRangeEnd");
$leasetime = get("","/runtime/hnap/SetNetworkSettings/LeaseTime");
$broadcast = get("","/runtime/hnap/SetNetworkSettings/Broadcast");
$dnsr = get("","/runtime/hnap/SetNetworkSettings/DNSRelay");

TRACE_error("dnsr=".$dnsr);

if($broadcast != "true" && $broadcast != "false") $result = "ERROR_BAD_BROADCAST";
if($dnsr != "true" && $dnsr != "false") 					$result = "ERROR_BAD_DNSRELAY";

if($result == "OK")
{
	$path_inf_lan1 = XNODE_getpathbytarget("", "inf", "uid", $LAN1, 0);
	$lan1_inet = get("", $path_inf_lan1."/inet");
	$path_inet_lan1 = XNODE_getpathbytarget("inet", "entry", "uid", $lan1_inet, 0);
	$dhcps4_lan1 = query($path_inf_lan1."/dhcps4");
	if($dhcps4_lan1=="") {$dhcps4_lan1="DHCPS4-1";} //If $dhcps4_lan1 is empty, it means the dhcp server in LAN1 is disabled.
	$path_dhcps4_lan1 = XNODE_getpathbytarget("dhcps4", "entry", "uid", $dhcps4_lan1, 0);
	$path_dhcps4_lan2 = XNODE_getpathbytarget("dhcps4", "entry", "uid", "DHCPS4-2", 0); // for guest zone
	TRACE_debug("path_inf_lan1=".$path_inf_lan1);
	TRACE_debug("path_inet_lan1=".$path_inet_lan1);
	TRACE_debug("path_dhcps4_lan1=".$path_dhcps4_lan1);
	
	$hostname = $DeviceName;
	set("/device/hostname", $hostname);
	if		 ($dnsr == "true")			set($path_inf_lan1."/dns4", "DNS4-1");
	else if($dnsr == "false")			set($path_inf_lan1."/dns4", "");
	
	set($path_dhcps4_lan1."/domain", $local_domain_name);
	set($path_dhcps4_lan1."/start", $start);
	set($path_dhcps4_lan1."/end", $end);
	set($path_dhcps4_lan1."/leasetime", $leasetime*60);
	if		 ($broadcast == "true")			{set($path_dhcps4_lan1."/broadcast", "yes");
											 set($path_dhcps4_lan2."/broadcast", "yes");
											}
	else if($broadcast == "false")			{set($path_dhcps4_lan1."/broadcast", "no");
											 set($path_dhcps4_lan2."/broadcast", "no");
											}

	$path_run_lan1 =  XNODE_getpathbytarget("/runtime", "inf", "inet/uid", $lan1_inet, 0);
	$run_ipaddr = query($path_run_lan1."/inet/ipv4/ipaddr");
	$run_mask = query($path_run_lan1."/inet/ipv4/mask");
	/*If the LAN IP address of router is changed, just save the settings and return REBOOT result.*/
	if($run_ipaddr!=$ipaddress || $run_mask!=ipv4mask2int($subnetmask))
	{
		set($path_inet_lan1."/ipv4/ipaddr", $ipaddress);
		set($path_inet_lan1."/ipv4/mask", ipv4mask2int($subnetmask));
		$result = "REBOOT";
		TRACE_debug("The LAN IP or subnet mask is changed!");
	}
}

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo \"[$0]\" > /dev/console\n");
if($result == "OK" || $result == "REBOOT")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
}
if($result == "OK")
{
	fwrite("a",$ShellPath, "service DEVICE.HOSTNAME restart > /dev/console\n");
	fwrite("a",$ShellPath, "service INET.LAN-1 restart > /dev/console\n");	
	fwrite("a",$ShellPath, "service DHCPS4.LAN-1 restart > /dev/console\n");
	fwrite("a",$ShellPath, "service DHCPS4.LAN-2 restart > /dev/console\n");	
	fwrite("a",$ShellPath, "service URLCTRL restart > /dev/console\n");
	fwrite("a",$ShellPath, "service WAN restart > /dev/console\n");
	fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");
	set("/runtime/hnap/dev_status", "ERROR");
}
else if($result!="OK" && $result!="REBOOT")
{
	fwrite("a",$ShellPath, "echo \"We got a error in setting, so we do nothing...\" > /dev/console\n");	
}
?>

<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<SetNetworkSettingsResponse xmlns="http://purenetworks.com/HNAP1/">
	<SetNetworkSettingsResult><?=$result?></SetNetworkSettingsResult>
	</SetNetworkSettingsResponse>
</soap:Body>
</soap:Envelope>
