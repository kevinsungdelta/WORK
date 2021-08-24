<? include "/htdocs/phplib/html.php";
if($Remove_XML_Head_Tail != 1)	{HTML_hnap_200_header();}

include "/htdocs/phplib/xnode.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/trace.php"; 

$radioID = get("","/runtime/hnap/GetWLanRadioSettings/RadioID");

if( $radioID == "2.4GHZ" || $radioID == "RADIO_24GHz" || $radioID == "RADIO_2.4GHz")
{	
	$path_phyinf_wlan_host = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1, 0);	
	$path_phyinf_wlan = $path_phyinf_wlan_host;
}
else if( $radioID == "5GHZ" || $radioID == "RADIO_5GHz")
{	
	$path_phyinf_wlan_host = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN2, 0);
	$path_phyinf_wlan = $path_phyinf_wlan_host;
}
else if( $radioID == "RADIO_2.4G_Guest" || $radioID == "RADIO_2.4GHz_Guest")
{
	/* The children nodes in $path_phyinf_wlan."/media" of guestzone is refer to hostzone ($path_phyinf_wlan_host). */
	$path_phyinf_wlan_host = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1, 0);
	$path_phyinf_wlan = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1_GZ, 0);	
} 
else if( $radioID == "RADIO_5G_Guest" || $radioID == "RADIO_5GHz_Guest")
{
	/* The children nodes in $path_phyinf_wlan."/media" of guestzone is refer to hostzone ($path_phyinf_wlan_host). */
	$path_phyinf_wlan_host = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN2, 0);
	$path_phyinf_wlan = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN2_GZ, 0);
} 

TRACE_debug("path_phyinf_wlan=".$path_phyinf_wlan);

$path_wlan_wifi = XNODE_getpathbytarget("/wifi", "entry", "uid", query($path_phyinf_wlan."/wifi"), 0);

if( $radioID != "2.4GHZ" && $radioID != "5GHZ" && $radioID != "RADIO_24GHz" && 
		$radioID != "RADIO_5GHz" && $radioID != "RADIO_2.4GHz" && $radioID != "RADIO_2.4G_Guest" && $radioID != "RADIO_5G_Guest" && $radioID != "RADIO_2.4GHz_Guest" && $radioID != "RADIO_5GHz_Guest")
{ $result = "ERROR_BAD_RADIO"; } 
else
{
	$result = "OK";
	$channel=query($path_phyinf_wlan_host."/media/channel");
	if(query($path_phyinf_wlan."/active")=="1" && query($path_phyinf_wlan_host."/media/channel")=="0")
	{
		//update channel value when autochannel setup for HNAP Spec.
		//$channel=query("/runtime/stats/wireless/channel");
		$channel="0";
	}
	$wlanMode = query($path_phyinf_wlan_host."/media/wlmode");
	TRACE_debug("wlanMode=".$wlanMode);
	
	$TXPower = query($path_phyinf_wlan_host."/media/txpower");
	
	$sch = get("x",$path_phyinf_wlan."/schedule");
	if($sch!="") { $Schedule = XNODE_getschedulename($sch); }
	else {$Schedule = "ALWAYS";}
	
	if( $wlanMode == "b" )		{ $wlanStr = "802.11b" ;}
	else if( $wlanMode == "g" )	{ $wlanStr = "802.11g"; }
	else if( $wlanMode == "bg" )	{ $wlanStr = "802.11bg"; }
	else if( $wlanMode == "n" )	{ $wlanStr = "802.11n"; }
	else if( $wlanMode == "bn" )	{ $wlanStr = "802.11bn"; }
	else if( $wlanMode == "gn" )	{ $wlanStr = "802.11gn"; }
	else if( $wlanMode == "bgn" )	{ $wlanStr = "802.11bgn"; }
	else if( $wlanMode == "a" )	{ $wlanStr = "802.11a"; }
	else if( $wlanMode == "an" )	{ $wlanStr = "802.11an"; }
	else if( $wlanMode == "ac" )	{ $wlanStr = "802.11ac"; }
	else if( $wlanMode == "acn" )	{ $wlanStr = "802.11nac"; }
	else if( $wlanMode == "acna" )	{ $wlanStr = "802.11anac"; }
	else						{ $result = "ERROR"; }
	$width = query($path_phyinf_wlan_host."/media/dot11n/bandwidth");
	if( $width == "20" )
	{ $bandWidth = "20"; }
	else if( $width == "40" )
	{ $bandWidth = "40"; }
	else if( $width == "80" )
	{ $bandWidth = "80"; }
	else if( $width == "20+40" )
	{ $bandWidth = "0"; }
	else if( $width == "20+40+80" )
	{ $bandWidth = "1"; }
	else
	{ $bandWidth = "0"; }
	$secondaryChnl = query("/wireless/SecondaryChannel"); 
	if($secondaryChnl == "")
	{
		$support11n = "1";
		if($support11n == "1")
		{
			$ccode = query("/runtime/devdata/countrycode");
			if( $ccode == "" )
			{
				$ccode = query("/runtime/devdata/countrycode");
			}		
			if( $ccode == "840" ) 
				{ $chnl_num = 11; }
			else if( $ccode == "826" || $ccode == "152" || $ccode == "392" )
				{ $chnl_num = 13; }
			else
				{ $chnl_num = 13; }   
			if( $bandWidth == 40 && $channel <= 4 )
			{	 
				$secondaryChnl = $channel + 4; 
			}
			else if( $channel > 4 && $channel < 8 && $bandWidth == 40 )
			{ 
				$secondaryChnl = $channel - 4; 
			}      
			else if( $channel >= 8 && $bandWidth == 40)
			{ 
				if( $chnl_num - $channel < 4 )
				{ $secondaryChnl = $channel - 4; }
				else
				{ $secondaryChnl = $channel - 4;}
			}
			else
			{
				$secondaryChnl = $channel;
			}	
		} 
		else if($width == "")
		{
			$secondaryChnl = 0;
		}
	
	}
	if(get("", $path_phyinf_wlan_host."/media/dot11n/bw2040coexist") == "1") {$Coexistence = "true";}
	else {$Coexistence = "false";}
	/*	RadioEnabled should be the same as Enabled in hostzone.
		RadioEnabled for guestzone should be disabled if hostzone is disabled.
		RadioEnabled for guestzone should be by itself if hostzone is enabled.*/
	if(get("", $path_phyinf_wlan_host."/active") == "0")	{$RadioEnabled = "false";}
	else	{$RadioEnabled = map($path_phyinf_wlan."/active", "1", "true", "*", "false");}

	$countrycode = query("/runtime/devdata/countrycode");
	$turbomode = query("/runtime/devdata/turbomode");
	$turbosupport = 0;
	if($countrycode == "SG" && $turbomode == "1")
		$turbosupport = 1;
}
?>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_header();}?>
		<GetWLanRadioSettingsResponse xmlns="http://purenetworks.com/HNAP1/">
			<GetWLanRadioSettingsResult><?=$result?></GetWLanRadioSettingsResult>
			<Mode><?=$wlanStr?></Mode>
			<Enabled><? echo map($path_phyinf_wlan."/active", "1", "true", "*", "false");?></Enabled>
			<MacAddress><? echo query("/runtime/devdata/lanmac");?></MacAddress>
			<SSID><? echo get("x",$path_wlan_wifi."/ssid");?></SSID>
			<SSIDBroadcast><? echo map($path_wlan_wifi."/ssidhidden", "1", "false", "*", "true");?></SSIDBroadcast>
			<ChannelWidth><?=$bandWidth?></ChannelWidth>
			<Channel><?=$channel?></Channel>
			<SecondaryChannel><?=$secondaryChnl?></SecondaryChannel>
			<QoS><?echo map($path_phyinf_wlan."/media/wmm/enable","0","false","*","true");?></QoS>
			<TXPower><?=$TXPower?></TXPower>
			<ScheduleName><?=$Schedule?></ScheduleName>
			<RadioEnabled><?=$RadioEnabled?></RadioEnabled>
			<Coexistence><?=$Coexistence?></Coexistence>
			<TurboMode><?=$turbosupport?></TurboMode>
		</GetWLanRadioSettingsResponse>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_tail();}?>
