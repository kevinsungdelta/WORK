<?
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inet.php";
include "/htdocs/phplib/inf.php";
include "/htdocs/phplib/phyinf.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/webinc/config.php";

function findthelastipv6($str)
{
	$i=1;
	while($i > 0)
	{
		$lastipv6=scut($str, $i-1, "");
		if(scut($str, $i, "")=="") break;
		else $i++;
	}
	return	$lastipv6;
}

function LanIPv6AddressRange($network, $start_addr, $count, $start_or_end)
{
	$start_ip_value_tmp = scut($start_addr, 0, $network);
	$start_ip_value = scut($start_ip_value_tmp, 0, "::");

	if($start_or_end == "start")
	{
		if(strlen($start_ip_value)==2) $start_ip_value = "00".$start_ip_value;
		else if (strlen($start_ip_value)==1) $start_ip_value = "000".$start_ip_value;
		$LanIPv6Addr = $network.$start_ip_value;
	}
	else if($start_or_end == "end")
	{
		$end_ip_value = strtoul($start_ip_value, 16) + $count - 1;
		$end_ip_value = dec2strf('%x', $end_ip_value);
		if(strlen($end_ip_value)==2) $end_ip_value = "00".$end_ip_value;
		else if (strlen($end_ip_value)==1) $end_ip_value = "000".$end_ip_value;
		$LanIPv6Addr = $network.$end_ip_value;
	}
	else return false;

	return $LanIPv6Addr;
}

function PPPBtnSetup($rwan, $wancable_status, $str_Connected, $str_Disconnected)
{
	$connStat = query($rwan."/pppd/status");

	if($connStat == "connected")
	{
		if($wancable_status == 1)
		{
			$str_networkstatus = $str_Connected;
		}
		else	$str_networkstatus = $str_Disconnected;
	}
	else if($connStat == "disconnected")
	{
		$str_networkstatus = $str_Disconnected;
		$wancable_status=0;
	}
	else	$str_networkstatus = "Busy ...";

	return $str_networkstatus;
}

// ############  START ############

$DEBUG_HNAP = "n";		//+++ Sammy, DEBUG mode default = n.

/*<--InitWAN()  */

// Step 1: Deciding IPv6 Wan by $addrtype.
$wan = $WAN4;	// IPv6 Default Wan is WAN-4.
if($wan == "")	TRACE_info("  ++ [Error]: $wan is empty.");

$wan1inetp = INET_getpathbyinf($WAN1);	// get from /inet/entry:3
if($wan1inetp=="")	TRACE_info("  ++ [Error]: $wan1_inetp is empty.");

$wan3inetp = INET_getpathbyinf($WAN3);  //get from /inet/entry:8

$is_ll  = 0;
$is_ppp6  = 0;
$is_ppp10 = 0;

//+++ Grace Lin, added for AutoDetection.
$wan5infp = INF_getinfpath($WAN5);  //get from /inf:12

if($wan5infp != "") { $active_wan5 = query($wan5infp."/active"); }

if($active_wan5 == "1")
{
	$str_wantype = "AUTO";

	$wan = $WAN5;
}
else
{
	if(query($wan3inetp."/addrtype") == "ppp6")			// Create new session.
	{
		$is_ppp6 = 1;

		$wan = $WAN3;
	}
	else if(query($wan1inetp."/addrtype") == "ppp10")	// Share with IPv4
	{
		$is_ppp10 = 1;

		$wan = $WAN1;
	}
}


// Step 2: Get Wan Profiles.
$rwan  = XNODE_getpathbytarget("/runtime", "inf", "uid", $wan, "0");
$rwan3 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN3, "0");

$infp  = XNODE_getpathbytarget("", "inf", "uid", $WAN4, "0");
$inet  = query($infp."/inet");
$inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, "0");

$infp  = XNODE_getpathbytarget("", "inf", "uid", $WAN3, "0");
$inet  = query($infp."/inet");
$inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, "0");

$wanll_type = get("",$rwan3."/inet/addrtype");
if ($wanll_type == "ipv6")
{ $wanlladdr = query($rwan3."/inet/ipv6/ipaddr");}
else if ($wanll_type == "ppp6" || $wanll_type == "ppp10" )
{ $wanlladdr = query($rwan3."/inet/ppp6/local"); }
// TRACE_error("  ++ $wanlladdr = ".$wanlladdr);

if(query(INF_getinfpath($wan)."/active") == "0" && $is_ppp6 != 1 && $is_ppp10 != 1)
{
	// Link-Local.
	$wan = $WAN3;

	//+++ Jerry Kao, Use /inet/entry instead of /runtime/inf, for Link-Local.
	$infp  = XNODE_getpathbytarget("", "inf", "uid", $WAN3, "0");
	$inet  = query($infp."/inet");
	$inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, "0");

	$rwan  = XNODE_getpathbytarget("/runtime", "inf", "uid", $wan, "0");
	//$rwan  = $inetp;

	$is_ll = 1;
}

$waninetuid = query(INF_getinfpath($wan)."/inet");
$wanphyuid  = query(INF_getinfpath($wan)."/phyinf");
$waninetp = XNODE_getpathbytarget("/inet", "entry", "uid", $waninetuid, "0");
$rwanphyp = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $wanphyuid, "0");


// Get Lan Profiles.
$lanulact_infp  = XNODE_getpathbytarget("", "inf", "uid", "LAN-6", 0);
$lanulact_inet  = get("",$lanulact_infp."/inet");
$lanulact_inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $lanulact_inet, 0);
$rlan6 = XNODE_getpathbytarget("/runtime", "inf", "uid", "LAN-6", "0");


$str_Connected     = "Connected";
$str_networkstatus = "Disconnected";
$str_Disconnected  = "Disconnected";

$wan_uptime       = query($rwan."/inet/uptime");
$system_uptime    = query("/runtime/device/uptime");
$wan_delta_uptime = $system_uptime - $wan_uptime;

$wancable_status = 0;
if(query($rwanphyp."/linkstatus")!="0" && query($rwanphyp."/linkstatus")!="")
{
   $wancable_status=1;
}

//+++ Jerry Kao, added for AP (Bridge mode), but Hard code.
$Layout = query("/device/layout");
if ($Layout == "bridge")
{
	$wancable_status = 1;
}


$rstlwan  = $rwan."/stateless";
$rwan_6rd = $rwan;	//If IPv6 WAN type is 6RD, It would be used.


if($DEBUG_HNAP == "y")
{
	TRACE_info("=== [Get_IPv6_Settings.php] DEBUG msgs ===");

	TRACE_info("  query($wan3inetp.'/addrtype') = ".query($wan3inetp."/addrtype"));
	TRACE_info("  query($wan1inetp.'/addrtype') = ".query($wan1inetp."/addrtype"));

	TRACE_info("  ++ $wan  = ".$wan);
	TRACE_info("  ++ $rwan = ".$rwan);
	TRACE_info("  ++ $waninetp = ".$waninetp);
	TRACE_info("  ++ $rwanphyp = ".$rwanphyp);

	TRACE_info("  ++ $is_ll    = ".$is_ll);
	TRACE_info("  ++ $is_ppp6  = ".$is_ppp6);
	TRACE_info("  ++ $is_ppp10 = ".$is_ppp10);
}


// Step 3: Decide WanType by flags ($is_ll, $is_ppp6, and $is_ppp10) and $addrtype.
if ($is_ll == 1)
{
	$str_wantype = "Link-Local";

	$str_wanipaddr  = "";
	$str_wanprefix  = "";
	$str_wangateway = "";
	$str_wanDNSserver  = "";
	$str_wanDNSserver2 = "";
}
//else if (query($waninetp."/addrtype")=="ipv6" && $wancable_status==1)
else if (query($waninetp."/addrtype")=="ipv6")
{
	// Part A: Common WAN_ip, Prefix, Gateway, and DNS Servers.
	if (query($waninetp."/ipv6/ipaddr")!="")
	{
		$str_wanipaddr = query($waninetp."/ipv6/ipaddr");
		$str_wanprefix = query($waninetp."/ipv6/prefix");
	}
	else
	{
		//+++ Jerry Kao, Use /inet/entry:(uid=WAN-4) instead of /runtime/inf:(uid=WAN-4)
		// Use Link-Local Address in WanType = Static.
		$str_wanipaddr  = query($rwanphyp."/ipv6/link/ipaddr");
		$str_wanprefix  = query($rwanphyp."/ipv6/link/prefix");
	}

	// Gateway
	$str_wangateway = query($waninetp."/ipv6/gateway");

	// DNS Servers
	if(query($waninetp."/ipv6/dns/entry:1")!="") $str_wanDNSserver = query($waninetp."/ipv6/dns/entry:1");
	else $str_wanDNSserver = "";

	if(query($waninetp."/ipv6/dns/entry:2")!="") $str_wanDNSserver2 = query($waninetp."/ipv6/dns/entry:2");
	else $str_wanDNSserver2 = "";


	// Part B: Getting WAN ip, Prefix, Gateway, and DNS Servers by Wan_Type.
	$str_wantype = query($waninetp."/ipv6/mode");

	if ($str_wantype == "6IN4")
	{
		$str_6in4_RemoteIPv4    = query($waninetp."/ipv6/ipv6in4/remote");
		$str_SubnetPrefixLength = query($waninetp."/ipv6/prefix");

		$str_wangateway = query($waninetp."/ipv6/gateway");

		//+++ Jerry Kao, Add Enable DHCP-PD.
		$child_infp = XNODE_getpathbytarget("", "inf", "uid", $wan, "0");
		$child = query($child_infp."/infnext");
	}
	else if ($str_wantype == "6TO4")
	{
		$rwan4 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN4, "0");
		$phyinf_uid = query($rwan4."/phyinf");
		$rphyp      = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $phyinf_uid, "0");

		$str_wanipaddr =  scut(query($rphyp."/ipv6/global/ipaddr"), 0, "");
		$str_wanprefix =  scut(query($rphyp."/ipv6/global/prefix"), 0, "");

		$str_wangateway = query($rwan4."/inet/ipv6/gateway");
	}
	else if ($str_wantype == "6RD")
	{
		$rwan4 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN4, "0");

		$str_wanipaddr = query($rwan4."/inet/ipv6/ipaddr");
		$str_wanprefix = query($rwan4."/inet/ipv6/prefix");

		$hub_spoke = query($waninetp."/ipv6/ipv6in4/rd/hubspokemode");
		if($hub_spoke == "1")	{ $str_hub_spoke = "Enable"; }
		else					{ $str_hub_spoke = "Disable";}

		if(query($waninetp."/ipv6/ipv6in4/rd/ipaddr")!="") { $str_6rd_Conf = "Manual"; }
		else 											   { $str_6rd_Conf = "DHCPv4_Option"; }

		if($str_6rd_Conf == "Manual")
		{
			$str_6rd_IPv6Prefix 	  = query($waninetp."/ipv6/ipv6in4/rd/ipaddr");
			$str_6rd_IPv6PrefixLength = query($waninetp."/ipv6/ipv6in4/rd/prefix");
			$str_6rd_IPv4MaskLength   = query($waninetp."/ipv6/ipv6in4/rd/v4mask");
		}
		else //DHCPv4_Option
		{
			$str_6rd_IPv6Prefix       = query($rwan4."/inet/ipv6/ipv6in4/rd/ipaddr");
			$str_6rd_IPv6PrefixLength = query($rwan4."/inet/ipv6/ipv6in4/rd/prefix");
			$str_6rd_IPv4MaskLength   = query($rwan4."/inet/ipv6/ipv6in4/rd/v4mask");
		}

		$str_wangateway = query($rwan4."/inet/ipv6/gateway");

		$str_wanDNSserver  = query($waninetp."/ipv6/dns/entry:1");
		$str_wanDNSserver2 = query($waninetp."/ipv6/dns/entry:2");

		$rlan4 = XNODE_getpathbytarget("/runtime", "inf", "uid", $LAN4, "0");
		$IPv6_6rd_assigned_prefix = get("",$rlan4."/dhcps6/network")."/".get("",$rlan4."/dhcps6/prefix");
	}
	else if($str_wantype == "AUTO" || $str_wantype == "Autoconfiguration") //Autoconfiguration
	{
		//if wan has more than one ipaddr, we need to get it by runtime phyinf and get the last one
		$str_wanipaddr = query($rwanphyp."/ipv6/global/ipaddr");
		$str_wanipaddr = findthelastipv6($str_wanipaddr);
		$str_wanprefix = query($rwanphyp."/ipv6/global/prefix");
		$str_wanprefix = findthelastipv6($str_wanprefix);

		$str_wangateway = query($rwan."/inet/ipv6/gateway");

		//+++ Jerry Kao, Add Enable DHCP-PD.
		$child_infp = XNODE_getpathbytarget("", "inf", "uid", $wan, "0");
		$child = query($child_infp."/child");
	}
	else if($str_wantype == "AUTODETECT") //AutoDetect
	{
		$rwan = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN4, "0");

		$infp  = XNODE_getpathbytarget("", "inf", "uid", $WAN4, "0");
		$inet  = query($infp."/inet");
		$inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, "0");
		$v6_mode   = query($inetp."/ipv6/mode");

		if($v6_mode == "6RD")
		{
			//when all /64 prefix assigned to LAN, WAN will have no ip address,thus using wangateway to determine network connected or not
			$str_wanipaddr = "";
		}
		else
		{
			//if wan has more than one ipaddr, we need to get it by runtime phyinf and get the last one
			$str_wanipaddr = query($rwanphyp."/ipv6/global/ipaddr");
			$str_wanipaddr = findthelastipv6($str_wanipaddr);
			if($str_wanipaddr == "")	$str_wanipaddr = query($rwanphyp."/ipv6/link/ipaddr");
		}
		$str_wanprefix = query($rwanphyp."/ipv6/global/prefix");
		$str_wanprefix = findthelastipv6($str_wanprefix);

		$str_wangateway = query($rwan."/inet/ipv6/gateway");

		//+++ Jerry Kao, Add Enable DHCP-PD.
		$child_infp = XNODE_getpathbytarget("", "inf", "uid", $wan, "0");
		$child = query($child_infp."/child");
	}

	// DNS Servers
	if($str_wanDNSserver == "" && $str_wanDNSserver2 == "")
	{
		if(query($rwan."/inet/ipv6/dns:1")!="") $str_wanDNSserver = query($rwan."/inet/ipv6/dns:1");
		else $str_wanDNSserver = "";

		if(query($rwan."/inet/ipv6/dns:2")!="") $str_wanDNSserver2 = query($rwan."/inet/ipv6/dns:2");
		else $str_wanDNSserver2 = "";
	}

	/*
	// DNS Servers
	if(query($rwan."/inet/ipv6/dns:1")!="") $str_wanDNSserver = query($rwan."/inet/ipv6/dns:1");
	else $str_wanDNSserver = "";

	if(query($rwan."/inet/ipv6/dns:2")!="") $str_wanDNSserver2 = query($rwan."/inet/ipv6/dns:2");
	else $str_wanDNSserver2 = "";
	*/

}
//else if ($is_ppp10 == 1 && $wancable_status==1)
else if ($is_ppp10 == 1)
{

	// PPPoEv6: Share with IPv4.
	$str_wantype = "PPPoE";

	$rwan  = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN1, "0");    // Original Code. // /runtime/inf:3
	$rwan4 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN4, "0");                      //  /runtime/inf:6

	// WAN-3 is child of WAN-1, in Share with IPv4.
	$child_uid  = query($rwan."/child/uid");
	$rwan_child = XNODE_getpathbytarget("/runtime", "inf", "uid", $child_uid, "0");	            // /runtime/inf:7
	$phyinf_uid = query($rwan_child."/phyinf");
	$rphyp      = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $phyinf_uid, "0");		    //  /runtime/phyinf:1

	$str_wanipaddr =  scut(query($rphyp."/ipv6/global/ipaddr"), 0, "");
	$str_wanprefix =  scut(query($rphyp."/ipv6/global/prefix"), 0, "");

	$str_wangateway = query($rwan."/child/ppp6/peer");

	//+++ Jerry Kao, modified for show user defined DNS primary in web.
	/*
	if(query($wan1inetp."/ppp6/dns/count") == "1")
	{
		$str_wanDNSserver  = query($rwan."/inet/ppp6/dns:1");
		$str_wanDNSserver2 = query($rwan4."/inet/ipv6/dns:1");
	}
	else if(query($wan1inetp."/ppp6/dns/count") == "2")
	{
		$str_wanDNSserver  = query($rwan."/inet/ppp6/dns:1");
		$str_wanDNSserver2 = query($rwan."/inet/ppp6/dns:2");
	}
	else
	{
		$str_wanDNSserver  = query($rwan4."/inet/ipv6/dns:1");
		$str_wanDNSserver2 = query($rwan4."/inet/ipv6/dns:2");
	}
	*/
	//+++ Jerry Kao, Add Enable DHCP-PD.
	$infp  = XNODE_getpathbytarget("", "inf", "uid", $WAN4, "0");
	$child = query($infp."/child");


	$ppp_infp  = XNODE_getpathbytarget("", "inf", "uid", $WAN1, "0");	    //    /inf:8
	$ppp_inet_name = get("",$ppp_infp."/inet");
	$ppp_inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $ppp_inet_name, "0");	 //   /inet/entry:3

	if ($ppp_inetp != "")
	{
		$str_wanDNSserver  = query($ppp_inetp."/ppp6/dns/entry:1");
		$str_wanDNSserver2 = query($ppp_inetp."/ppp6/dns/entry:2");
	}

	//+++ ipv6 wan dns, add by chris zheng 2014/06/11
	/* not a good way
	$path_run_inf_wan4 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN4, "0");

	if($path_run_inf_wan4 != "")
	{
		$str_wanDNSserver = query($path_run_inf_wan4."/inet/ipv6/dns:1");
		$str_wanDNSserver2 = query($path_run_inf_wan4."/inet/ipv6/dns:2");
	}
	*/

}
//else if ($is_ppp6 == 1 && $wancable_status==1)
else if ($is_ppp6 == 1)
{
	// PPPoEv6: Create New Session.
	$str_wantype = "PPPoE";

	$rwan  = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN3, "0");         //  /runtime/inf:4
	$rwan4 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN4, "0");

	$phyinf_uid = query($rwan."/phyinf");
	$rphyp      = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $phyinf_uid, "0");		//  /runtime/phyinf:6

	$str_wanipaddr =  scut(query($rphyp."/ipv6/global/ipaddr"), 0, "");
	$str_wanprefix =  scut(query($rphyp."/ipv6/global/prefix"), 0, "");

	//$str_wanipaddr  = query($rwan."/ppp6/local");
	//$str_wanprefix  = "64";

	$str_wangateway = query($rwan."/inet/ppp6/peer");

	//+++ Jerry Kao, modified for show user defined DNS primary in web.
	/*if(query($wan3inetp."/ppp6/dns/count") == "1")
	{
		$str_wanDNSserver  = query($rwan."/inet/ppp6/dns:1");
		$str_wanDNSserver2 = query($rwan4."/inet/ipv6/dns:1");
	}
	else if(query($wan3inetp."/ppp6/dns/count") == "2")
	{
		$str_wanDNSserver  = query($rwan."/inet/ppp6/dns:1");
		$str_wanDNSserver2 = query($rwan."/inet/ppp6/dns:2");
	}
	else
	{
		$str_wanDNSserver  = query($rwan4."/inet/ipv6/dns:1");
		$str_wanDNSserver2 = query($rwan4."/inet/ipv6/dns:2");
	}
*/
	//+++ Jerry Kao, Add Enable DHCP-PD.
	$infp  = XNODE_getpathbytarget("", "inf", "uid", $WAN4, "0");
	$child = query($infp."/child");

	$ppp_infp  = XNODE_getpathbytarget("", "inf", "uid", $WAN3, "0");
	$ppp_inet_name = get("",$ppp_infp."/inet");
	$ppp_inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $ppp_inet_name, "0");

	if ($ppp_inetp != "")
	{
		$str_wanDNSserver  = query($ppp_inetp."/ppp6/dns/entry:1");
		$str_wanDNSserver2 = query($ppp_inetp."/ppp6/dns/entry:2");
	}


	//+++ ipv6 wan dns, add by chris zheng 2014/06/11
	/* not a good way
	$path_run_inf_wan4 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN4, "0");

	if($path_run_inf_wan4 != "")
	{
		$str_wanDNSserver = query($path_run_inf_wan4."/inet/ipv6/dns:1");
		$str_wanDNSserver2 = query($path_run_inf_wan4."/inet/ipv6/dns:2");
	}
	*/
}


// For IPv6 Status [ in GetIPv6Status.php ].
$StatusV6_wanipaddr      = $str_wanipaddr;
$StatusV6_DefaultGateway = $str_wangateway;
//$StatusV6_wanDNSserver   = $str_wanDNSserver;
//$StatusV6_wanDNSserver2  = $str_wanDNSserver2;

// a better way to get DNS info, merge from branches 868.
if($str_wanDNSserver == "")
{
	$StatusV6_wanDNSserver   = query($rwan4."/inet/ipv6/dns:1");
	$StatusV6_wanDNSserver2  = query($rwan4."/inet/ipv6/dns:2");
}
else if($str_wanDNSserver != "" && $str_wanDNSserver2 == "")
{
	if($str_wantype == "PPPoE")
	{
		$StatusV6_wanDNSserver   = $str_wanDNSserver;
		$StatusV6_wanDNSserver2  = query($rwan4."/inet/ipv6/dns:1");
	}
	else
	{
		$StatusV6_wanDNSserver   = query($rwan."/inet/ipv6/dns:1");
		$StatusV6_wanDNSserver2  = query($rwan."/inet/ipv6/dns:2");
	}
}
else
{
	$StatusV6_wanDNSserver   = $str_wanDNSserver;
	$StatusV6_wanDNSserver2  = $str_wanDNSserver2;
}


if($wancable_status == 0)
{
	if(query($waninetp."/addrtype")=="ipv6") $str_wantype = query($waninetp."/ipv6/mode");
	else if($is_ppp6==1 || $is_ppp10==1)	$str_wantype = "PPPoE";

	$StatusV6_wanipaddr      = "";
	$StatusV6_DefaultGateway = "";
	$StatusV6_wanDNSserver   = "";
	$StatusV6_wanDNSserver2  = "";
}

if($is_ppp6 == 1)
{
	$str_status = PPPBtnSetup($rwan, $wancable_status, $str_Connected, $str_Disconnected);
	/* fix issue that only ppp6 session may show Busy status because ipcp is not completed */
	if($wancable_status==1 && $StatusV6_DefaultGateway !="")
	{
		$str_status = $str_Connected;
	}
	$wan_status = $str_status;
}
else if($is_ll != 1)
{
	$infp  = XNODE_getpathbytarget("", "inf", "uid", $WAN4, "0");
	$inet  = query($infp."/inet");
	$inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, "0");
	$v6_mode   = query($inetp."/ipv6/mode");

	//for 6rd, when all /64 prefix assigned to LAN, WAN will have no ip address,thus using wangateway to determine network connected or not
	if($str_wantype == "6RD" && $str_6rd_Conf == "DHCPv4_Option")
	{
		if($wancable_status == 1 && $str_wangateway != "")	$wan_status = $str_Connected;
		else	$wan_status = $str_Disconnected;
	}
	else if($str_wantype == "AUTODETECT" && $v6_mode == "6RD") //Autodetect && detected type is 6rd
	{
		if($wancable_status == 1 && $str_wangateway != "")	$wan_status = $str_Connected;
		else	$wan_status = $str_Disconnected;
	}
	else
	{
		if($wancable_status == 1 && $str_wangateway != "")	$wan_status = $str_Connected;
		else	$wan_status = $str_Disconnected;
	}
}
else
	$wan_status = $str_Disconnected;

/* HuanYao Kang: set uptime as 0 if status is disconnected. */
if ($wan_status == $str_Disconnected)
{ $wan_delta_uptime = 0; }


if($is_ll != 1)
{
	if($str_wantype == "STATIC")
		$str_wantype = "Static";
	else if($str_wantype == "AUTO")
	{
		$rwan4 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN4, "0");
		$rwanmode = query($rwan4."/inet/ipv6/mode");

		if($rwanmode=="STATEFUL")       { $str_wantype = "DHCPv6"; }
		else if($rwanmode=="STATELESS") { $str_wantype = "SLAAC";  }
		else                            { $str_wantype = "Autoconfiguration"; }

		//+++ Jerry Kao, Add Enable DHPC-PD.
		$child_infp = XNODE_getpathbytarget("", "inf", "uid", $WAN4, "0");
		$child = query($child_infp."/child");
	}
}

//+++ Jerry Kao, Add Enable DHCP-PD.
$enpd = "0";	// Enable DHCP-PD.
if($child!="")	{ $enpd = "Enable"; }
else			{ $enpd = "Disable";}

/*  InitWAN()-->*/

/*<--InitLAN()  */
$lan = INF_getinfpath($LAN4);
$infp  = XNODE_getpathbytarget("", "inf", "uid", $LAN4, "0");
$inet  = query($infp."/inet");
$inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, "0");
$rlan = $inetp;

$dhcps6p = XNODE_getpathbytarget("/dhcps6", "entry", "uid", query($lan."/dhcps6"), "0");
$inetuid = query($lan."/inet");
$phyuid = query($lan."/phyinf");
$rlanphyp = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $phyuid, "0");

if($DEBUG_HNAP == "y")
{
	if($lan=="")	  TRACE_debug("$lan is empty.");
	if($rlan=="")	  TRACE_debug("$rlan is empty.");
	if($inetuid=="")  TRACE_debug("$inetuid is empty.");
	if($phyuid=="")	  TRACE_debug("$phyuid is empty.");
	if($rlanphyp=="") TRACE_debug("$rlanphyp is empty.");
}

if($is_ll==1)
{
	$ll_lan_ll_address = query($rlanphyp."/ipv6/link/ipaddr");
	$ll_lan_ll_pl = "64";
}
else
{
	$lan_ll_address = query($rlanphyp."/ipv6/link/ipaddr");
	$lan_ll_pl = "64";
}


$pdnetwork = query($rwan4."/child/pdnetwork");
$pdprefix  = query($rwan4."/child/pdprefix");

if($pdnetwork != "")
{
	$pd_prefix    = $pdnetwork;
	$pd_prefixlen = "/".$pdprefix;
	$pd_prefix = $pd_prefix.$pd_prefixlen;
}
else
{
	$pd_prefix    = "";
	$pd_prefixlen = "";
}

if($inet == "") //LAN DHCP-PD is enable
{
	$lanip = query($rlanphyp."/ipv6/global/ipaddr");
	$prefix = query($rlanphyp."/ipv6/global/prefix");
}
else
{
$lanip = query($rlan."/ipv6/ipaddr");
$prefix = query($rlan."/ipv6/prefix");
}

if($lanip != "")
{
	$lan_addr = $lanip;
	$lan_pl = "/".$prefix;
}
else
{
	$lan_addr = "";
	$lan_pl = "";
}

/*  InitLAN()-->*/


/*********************************Guest Zone************************************/
/*<--InitG() GUEST Zone */
$gz_lan = INF_getinfpath("LAN-5");
$gz_infp  = XNODE_getpathbytarget("", "inf", "uid", "LAN-5", "0");
$gz_inet  = query($gz_infp."/inet");
$gz_inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $gz_inet, "0");
$gz_rlan = $gz_inetp;

$gz_dhcps6p = XNODE_getpathbytarget("/dhcps6", "entry", "uid", query($gz_lan."/dhcps6"), "0");
$gz_inetuid = query($gz_lan."/inet");
$gz_phyuid = query($gz_lan."/phyinf");
$gz_rlanphyp = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $gz_phyuid, "0");


$gz_LanLinkLocalAddress = query($gz_rlanphyp."/ipv6/link/ipaddr");

if($gz_inet == "") //LAN DHCP-PD is enable
{
	$gz_lanip = query($gz_rlanphyp."/ipv6/global/ipaddr");
	$gz_prefix = query($gz_rlanphyp."/ipv6/global/prefix");
}
else
{
	$gz_lanip = query($gz_rlan."/ipv6/ipaddr");
	$gz_prefix = query($gz_rlan."/ipv6/prefix");
}

if($gz_lanip != "")
{
	$gz_lan_addr = $gz_lanip;
	$gz_lan_pl = "/".$gz_prefix;
}
else
{
	$gz_lan_addr = "";
	$gz_lan_pl = "";
}
/*********************************Guest Zone************************************/

$wan4inetp = INET_getpathbyinf($WAN4);
$rwan1 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN1, "0");
//TRACE_info("  [ Get IPv6 Setting ]: $str_wantype = ".$str_wantype);

if($str_wantype == "Link-Local")
{
	$ConnectionType = "IPv6_LinkLocalOnly";
	$LanLinkLocalAddress 			 = $ll_lan_ll_address;
	$LanLinkLocalAddressPrefixLength = $ll_lan_ll_pl;

	//for ULA
	$active = get("",$lanulact_infp."/active");
	//RACE_debug("=== LL_ULA ===".$active);
	if($active == "1")	{	$en_ula = "Enable";	}
	else	{	$en_ula = "Disable";	}

	$isStatic = get("",$lanulact_inetp."/ipv6/staticula");
	$ula_ipaddr = get("",$lanulact_inetp."/ipv6/ipaddr");

	if($isStatic == "1")
	{
		$use_default_ula = "Disable";
	}
	else
	{
		$use_default_ula = "Enable";
	}
	$ula_prefix	= $ula_ipaddr;
	$ula_prelen = get("",$lanulact_inetp."/ipv6/prefix");
	$ula_addr = get("",$rlan6."/inet/ipv6/ipaddr");

}
else if($str_wantype == "Static")
{
	$ConnectionType = "IPv6_Static";

	if($str_wanipaddr == INF_getcurripaddr($WAN3)) $UseLinkLocalAddress = "Enable";
	else $UseLinkLocalAddress = "Disable";

	$Address = $str_wanipaddr;

	$SubnetPrefixLength = $str_wanprefix;
	$DefaultGateway     = $str_wangateway;		// for GetIPv6Status.php
}
else if($str_wantype == "6IN4")
{
	$ConnectionType = "IPv6_IPv6InIPv4Tunnel";

	$6In4LocalIPv4Address   = INF_getcurripaddr($WAN1);
	$6In4LocalIPv6Address   = $str_wanipaddr;

	$6In4RemoteIPv4Address  = $str_6in4_RemoteIPv4;
	$6In4RemoteIPv6Address  = $str_wangateway;
	$6In4SubnetPrefixLength = $str_SubnetPrefixLength;

	$DefaultGateway = $str_wangateway;
}
else if($str_wantype == "6TO4")
{
	$ConnectionType = "IPv6_6To4";

	$6To4Address = $str_wanipaddr;
	//$6To4Relay   = $str_wangateway;
	$6To4Relay   = query($waninetp."/ipv6/ipv6in4/relay");

	if(query($wan4inetp."/ipv6/ipv6in4/ipv6to4/slaid") == "")
	{
		$slaid = "1";
	}
	else
	{
		$slaid_tmp = query($wan4inetp."/ipv6/ipv6in4/ipv6to4/slaid");
		$slaid = dec2strf('%x', $slaid_tmp);
	}

	$DefaultGateway = $str_wangateway;
}
else if($str_wantype == "6RD")
{
	$ConnectionType = "IPv6_6RD";

	$6Rd_Hub_Spoke	    = $str_hub_spoke;
	$6Rd_Configuration  = $str_6rd_Conf;
	$6Rd_IPv4Address    = INF_getcurripaddr($WAN1);
	$6Rd_IPv4MaskLength = $str_6rd_IPv4MaskLength;

	$6Rd_IPv6Prefix             = $str_6rd_IPv6Prefix;
	$6Rd_IPv6PrefixLength       = $str_6rd_IPv6PrefixLength;

	if($6Rd_Configuration == "Manual")	$6Rd_BorderRelayIPv4Address = query($waninetp."/ipv6/ipv6in4/relay");
	else $6Rd_BorderRelayIPv4Address = query($rwan1."/udhcpc/sixrd_brip");

	$6Rd_LanAddress             = $lanip;

	$DefaultGateway = $str_wangateway;
}
else if($str_wantype=="AUTO" || $str_wantype=="DHCPv6" || $str_wantype=="SLAAC" || $str_wantype=="Autoconfiguration" || $str_wantype=="AUTODETECT")
{
	if($wan5infp != "")
	{
		if(query($wan5infp."/active")=="1")
		{
			$ConnectionType  = "IPv6_AutoDetection";
			$ConnectionType2 = "IPv6_AutoConfiguration";
		}
		else
			$ConnectionType = "IPv6_AutoConfiguration";
	}
	else
	{
		$ConnectionType = "IPv6_AutoConfiguration";
	}
	$Address = $str_wanipaddr;
	$SubnetPrefixLength = $str_wanprefix;
	$DefaultGateway     = $str_wangateway;
}
else if($str_wantype == "PPPoE")
{
	if(query(INF_getinfpath($WAN5)."/active")=="1")
	{
		$ConnectionType  = "IPv6_AutoDetection";
		$ConnectionType2 = "IPv6_DynamicPPPoE";
	}
	else if(query($wan1inetp."/addrtype")=="ppp10")
	{
		if(query($wan1inetp."/ppp6/static")=="1")
		{
			$ConnectionType = "IPv6_PPPoE";

			$Pppoetype = "Static";
			$Address   = query($wan1inetp."/ipv6/ipaddr");
		}
		else
		{
			$ConnectionType = "IPv6_PPPoE";
			$Pppoetype      = "Dynamic";
		}

		$PppoeNewSession = "SharedWithIPv4";

		$PppoeUsername = query($wan1inetp."/ppp6/username");
		$PppoePassword = query($wan1inetp."/ppp6/password");

		if(query($wan1inetp."/ppp6/dialup/mode") != "manual")
		{
			$PppoeReconnectMode = "AlwaysOn";
		}
		else
		{
			$PppoeReconnectMode = "Manual";
		}
		$PppoeMaxIdleTime   = query($wan1inetp."/ppp6/dialup/idletimeout");
		$PppoeMTU           = query($wan1inetp."/ppp6/mtu");
		$PppoeServiceName   = query($wan1inetp."/ppp6/pppoe/servicename");
	}
	else //ppp6
	{
		if(query($wan3inetp."/ppp6/static")=="1")
		{
			$ConnectionType = "IPv6_PPPoE";

			$Pppoetype = "Static";
			$Address   = query($wan3inetp."/ppp6/ipaddr");
		}
		else
		{
			$ConnectionType = "IPv6_PPPoE";
			$Pppoetype      = "Dynamic";
		}

		$PppoeNewSession = "NewSession";

		$PppoeUsername      = query($wan3inetp."/ppp6/username");
		$PppoePassword      = query($wan3inetp."/ppp6/password");
		if(query($wan3inetp."/ppp6/dialup/mode") != "manual")
		{
			$PppoeReconnectMode = "AlwaysOn";
		}
		else
		{
			$PppoeReconnectMode = "Manual";
		}
		$PppoeMaxIdleTime   = query($wan3inetp."/ppp6/dialup/idletimeout");
		$PppoeMTU           = query($wan3inetp."/ppp6/mtu");
		$PppoeServiceName   = query($wan3inetp."/ppp6/pppoe/servicename");
	}

	$SubnetPrefixLength = $str_wanprefix;
	$DefaultGateway     = $str_wangateway;
}

if($ConnectionType != "IPv6_LinkLocalOnly")
{
	// DNS Settings.
	if($ConnectionType=="IPv6_PPPoE")
	{
		if($PppoeNewSession=="NewSession")
		{
			if(query($wan3inetp."/ppp6/dns/count")!="0") $ObtainDNS = "Manual";
			else $ObtainDNS = "Automatic";
		}
		else //SharedWithIPv4
		{
			if(query($wan1inetp."/ppp6/dns/count")!="0") $ObtainDNS = "Manual";
			else $ObtainDNS = "Automatic";
		}
	}
	else if($ConnectionType=="IPv6_AutoDetection")
	{
		if(query($waninetp."/ipv6/dns/count")!="0") $ObtainDNS = "Manual";
		else $ObtainDNS = "Automatic";
	}
	else if($ConnectionType!="IPv6_Static" && $ConnectionType!="IPv6_6To4" && $ConnectionType!="IPv6_6RD")
	{
		if(query($wan4inetp."/ipv6/dns/count")!="0") $ObtainDNS = "Manual";
		else $ObtainDNS = "Automatic";
	}
 	$PrimaryDNS   = $str_wanDNSserver;
	$SecondaryDNS = $str_wanDNSserver2;

	// LAN IPV6 Address Settings.
	if($ConnectionType!="IPv6_Static" && $ConnectionType!="IPv6_6To4" && $ConnectionType!="IPv6_6RD")
	{
		$DhcpPd = $enpd;
	}
	$LanAddress = $lanip;
	$LanAddressPrefixLength = $prefix;
	$LanLinkLocalAddress    = $lan_ll_address;
	$LanLinkLocalAddressPrefixLength = $lan_ll_pl;

	//for ULA
	$active = get("",$lanulact_infp."/active");
	//TRACE_debug("=== NOT LL_ULA ===".$active);
	if($active == "1")	{	$en_ula = "Enable";	}
	else	{	$en_ula = "Disable";	}


	// ADDRESS AUTOCONFIGURATION SETTINGS
	if(query(INF_getinfpath($LAN4)."/dhcps6") != "")
	{
		$LanIPv6AddressAutoAssignment = "Enable";
		if(query($dhcps6p."/pd/enable")=="1") $LanAutomaticDhcpPd = "Enable";
		else $LanAutomaticDhcpPd = "Disable";
	}
	else
	{
		$LanIPv6AddressAutoAssignment = "Disable";
		$LanAutomaticDhcpPd = "Disable";
	}

	if(query($dhcps6p."/mode")=="STATELESS" && query("/device/rdnss")=="1")
	{
		$LanAutoConfigurationType = "SLAAC_RDNSS";
		if(query(INET_getpathbyinf($LAN4)."/ipv6/routerlft") == "") $LanRouterAdvertisementLifeTime = "";
		else $LanRouterAdvertisementLifeTime = query(INET_getpathbyinf($LAN4)."/ipv6/routerlft")/60;
	}
	else if(query($dhcps6p."/mode")=="STATELESS" && query("/device/rdnss")=="0")
	{
		$LanAutoConfigurationType = "SLAAC_StatelessDhcp";
		if(query(INET_getpathbyinf($LAN4)."/ipv6/routerlft") == "") $LanRouterAdvertisementLifeTime = "";
		else $LanRouterAdvertisementLifeTime = query(INET_getpathbyinf($LAN4)."/ipv6/routerlft")/60;
	}
	else if(query($dhcps6p."/mode")=="STATEFUL")
	{
		$LanAutoConfigurationType = "Stateful";

		$LanIPv6AddressRangeStart_tmp = LanIPv6AddressRange(query($rlan."/dhcps6/network"), query($dhcps6p."/start"), query($dhcps6p."/count"), "start");
		$LanIPv6AddressRangeStart = scut($LanIPv6AddressRangeStart_tmp, 0, "00");

		$LanIPv6AddressRangeEnd_tmp = LanIPv6AddressRange(query($rlan."/dhcps6/network"), query($dhcps6p."/start"), query($dhcps6p."/count"), "end");
		$LanIPv6AddressRangeEnd = scut($LanIPv6AddressRangeEnd_tmp, 0, "00");

		if(query(INET_getpathbyinf($LAN4)."/ipv6/preferlft") == "") $LanDhcpLifeTime = "";
		else $LanDhcpLifeTime = query(INET_getpathbyinf($LAN4)."/ipv6/preferlft")/60;
	}
}

//+++ Jerry Kao, add for show Link-Local Address for Bridge mode.
if ($Layout == "bridge")
{
	$LanLinkLocalAddress = query($rwanphyp."/ipv6/link/ipaddr");
}

//+++ Jerry Kao, Set all cases are OK for test...
$result = "OK";
$rlt = "true";

if($DEBUG_HNAP == "y")
{
	TRACE_info("=== [ Get_IPv6_Settings.php ] DEBUG msgs 2 ===");

	TRACE_info("  ++ [str_wantype] = ".$str_wantype);
	TRACE_info("  ++ [ConnectionType] = ".$ConnectionType);

	TRACE_info("  ++ $is_ll    = ".$is_ll);
	TRACE_info("  ++ $is_ppp6  = ".$is_ppp6);
	TRACE_info("  ++ $is_ppp10 = ".$is_ppp10);

	TRACE_info("  ++ $wan  = ".$wan);
	TRACE_info("  ++ $rwan = ".$rwan);
	TRACE_info("  ++ $rlan = ".$rlan);
	TRACE_info("  ++ $waninetp = ".$waninetp);
	TRACE_info("  ++ $rwanphyp = ".$rwanphyp);

	if($str_wantype == "Link-Local")
	{
		TRACE_info("  [Link-Local] Still doing ...");

	}
	else if ($str_wantype == "Static")
	{
		TRACE_info("  [STATIC] Still doing ...");

	}
	else if ($str_wantype == "PPPoE")
	{
		TRACE_info("$is_ppp6  [Create]  = ".$is_ppp6);
		TRACE_info("$is_ppp10 [Share ]  = ".$is_ppp10);
	}


	TRACE_info("  ++ $str_wanipaddr  = ".$str_wanipaddr);
	TRACE_info("  ++ $str_wanprefix  = ".$str_wanprefix);
	TRACE_info("  ++ $str_wangateway = ".$str_wangateway);
	TRACE_info("  ++ $str_wanDNSserver  = ".$str_wanDNSserver);
	TRACE_info("  ++ $str_wanDNSserver2 = ".$str_wanDNSserver2);
	TRACE_info("  ++ $DhcpPd = ".$DhcpPd);
	TRACE_info("  ++ $lanip = ".$lanip);
	TRACE_info("  ++ $prefix(lanip) = ".$prefix);
	TRACE_info("  ++ $LanAutoConfigurationType = ".$LanAutoConfigurationType);
	TRACE_info("  ++ $LanRouterAdvertisementLifeTime = ".$LanRouterAdvertisementLifeTime);
	TRACE_info("  ++ $LanIPv6AddressRangeStart = ".$LanIPv6AddressRangeStart);
	TRACE_info("  ++ $LanIPv6AddressRangeEnd   = ".$LanIPv6AddressRangeEnd);
	TRACE_info("  ++ $LanDhcpLifeTime = ".$LanDhcpLifeTime);
	TRACE_info("  ++ $LanLinkLocalAddress = ".$LanLinkLocalAddress);
	TRACE_info("  ++ $LanIPv6AddressAutoAssignment = ".$LanIPv6AddressAutoAssignment);
	TRACE_info("  ++ $LanAutomaticDhcpPd = ".$LanAutomaticDhcpPd);
}

?>
