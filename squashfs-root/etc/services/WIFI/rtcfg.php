<? /* vi: set sw=4 ts=4: */
/********************************************************************************
 *	NOTE: 
 *		The commands in this configuration generator is for Broadcom wireless.
 *******************************************************************************/
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/phyinf.php";
include "/htdocs/phplib/trace.php";
include "/etc/services/PHYINF/phywifi.php";
include "/htdocs/webinc/config.php";

/***************************** functions ************************************/
function wmm_paramters($wlif_bss_idx)
{
	/* Wifi-WMM parameters */
	echo "nvram set wl".$wlif_bss_idx."_wme_ap_be=\"15 63 3 0 0 off off\"\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_ap_bk=\"15 1023 7 0 0 off off\"\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_ap_vi=\"7 15 1 6016 3008 off off\"\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_ap_vo=\"3 7 1 3264 1504 off off\"\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_apsd=on\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_bss_disable=0\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_no_ack=off\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_sta_be=\"15 1023 3 0 0 off off\"\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_sta_bk=\"15 1023 7 0 0 off off\"\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_sta_vi=\"7 15 2 6016 3008 off off\"\n";
	echo "nvram set wl".$wlif_bss_idx."_wme_sta_vo=\"3 7 2 3264 1504 off off\"\n";
}

function country_setup($wlif_bss_idx, $ccode, $wlif_is_5g, $pci_2g_path, $pci_5g_path)
{
	/* No-DFS: No use some special channels.
	 * DFS-enable: if detect special channel, then will not use it auto. 
	 * Our design will use No-DFS most.
	 * For SR and RU, they just only have two options: one is DFS enable or no use 5G band. 
	 */
	$ctry_code = $ccode;
	
	if ($ccode == "US") 
	{
		$regrev = 0;
		//$ctry_code = "Q2";	
	}
	else if ($ccode == "CN") 
		$regrev = 0;
	else if ($ccode == "TW")
		$regrev = 0;
	else if ($ccode == "CA") 
	{
		$regrev = 0;
		//$ctry_code = "Q2";	
	}
	else if ($ccode == "KR")
		$regrev = 1;
	else if ($ccode == "JP")
		$regrev = 1;
	else if ($ccode == "AU")
		$regrev = 0;
	else if ($ccode == "SG")
	{
		/* Singaport two choice:
			1. DFS enable= SG/0  or 
			2. No use 5G band= SG/1
		*/
		$regrev = 0;
	}
	else if ($ccode == "LA")
		$regrev = 0;
	else if ($ccode == "IL")
		$regrev = 0;
	else if ($ccode == "EG")
		$regrev = 0;
	else if ($ccode == "BR")
		$regrev = 0;
	else if ($ccode == "RU")
	{
		/* Russia two choice:
			1. DFS enable= RU/1  or 
			2. No use 5G band= RU/0
		*/
		$regrev = 1;
	}
	else if ($ccode == "GB" || $ccode == "EU")
		$regrev = 0;
	else
		$regrev = 0;

	if ($wlif_is_5g == 1)
	{
		/*echo "nvram set ".$pci_5g_path."regrev=".$regrev."\n";
		echo "nvram set ".$pci_5g_path."ccode=".$ctry_code."\n";*/
		echo "nvram set ".$pci_5g_path."regrev=0\n";
		echo "nvram set ".$pci_5g_path."ccode=0\n";
	}
	else
	{
		/*echo "nvram set ".$pci_2g_path."regrev=".$regrev."\n";
		echo "nvram set ".$pci_2g_path."ccode=".$ctry_code."\n";*/
		echo "nvram set ".$pci_2g_path."regrev=0\n";
		echo "nvram set ".$pci_2g_path."ccode=0\n";
	}
	
	echo "nvram set wl".$wlif_bss_idx."_country_code=".$ctry_code."\n";
	echo "nvram set wl".$wlif_bss_idx."_country_rev=".$regrev."\n";
	/* alpha create nvram parameter: it's value include country code and regulatory revision */
	echo "nvram set wl".$wlif_bss_idx."_alpha_country_code=".$ctry_code."/".$regrev."\n";
}

function set_sta_mode($wl_prefix , $enabled)
{
	set("/runtime/wifi/".$wl_prefix."/sta" , $enabled);
}

function dev_stop($uid)
{
	$dev_name = devname($uid);
	$guestzone = isguestzone($uid);

	if ($guestzone == 1)
	{
		$dev_name = devname($uid);
	} else {
		$wlif_bss_idx = get_wlif_bss($uid);
		$dev_name = "wl".$wlif_bss_idx;
	}
	echo "nvram set ".$dev_name."_bss_enabled=0\n";
	echo "nvram set ".$dev_name."_radio=0\n";

	set_sta_mode($dev_name , "0");
}

function dev_init($pci_2g_path, $pci_5g_path)
{
	foreach ("/phyinf")
	{
		if (query("type")!="wifi") continue;
		$uid = query("uid");

		//wifi repeater profile is not a real phy interface, we don't need to initialize it
		if(is_repeater_mode($uid) == 1)
			continue;

		$wlif_bss_idx = get_wlif_bss($uid);
		$dev_name = devname($uid);
		$guestzone = isguestzone($uid);

		echo "# ".$uid."\n";
		echo "nvram set wl".$wlif_bss_idx."_ifname=".$dev_name."\n";
		dev_stop($uid);
		if ($guestzone != 1)
		{
			$prefix = cut($uid, 0, ".");
			$guest_uid = $prefix.".2";
			$dev_name = devname($guest_uid);
			echo "nvram set wl".$wlif_bss_idx."_vifs=".$dev_name."\n";

			if(isband5g($uid) == 1)
			{
				$wmac5addr  = PHYINF_getdevdatamac("wlanmac2");
				echo "nvram set ".$pci_5g_path."macaddr=".$wmac5addr."\n";				
			}
			else
			{
				$wmac24addr = PHYINF_getdevdatamac("wlanmac");
				echo "nvram set ".$pci_2g_path."macaddr=".$wmac24addr."\n";				
			}
		}
	}
}

/* let guestzone_mac = host_mac + 1*/
function get_guestzone_mac($host_mac)
{
	$index = 5;
	$guestzone_mac = "";
	$carry = 0;

	//loop from low byte to high byte
	//ex: 00:01:02:03:04:05
	//05 -> 04 -> 03 -> 02 -> 01 -> 00
	while($index >= 0)
	{
		$field = cut($host_mac , $index , ":");

		//check mac format
		if($field == "")
			return "";

		//to value
		$value = strtoul($field , 16);
		if($value == "")
			return "";

		if($index == 5)
			$value = $value + 1;

		//need carry?
		$value = $value + $carry;
		if($value > 255)
		{
			$carry = 1;
			$value = $value % 256;
		}
		else
			$carry = 0;

		//from dec to hex
		$hex_value = dec2strf("%02X" , $value);

		if($guestzone_mac == "")
			$guestzone_mac = $hex_value;
		else
			$guestzone_mac = $hex_value.":".$guestzone_mac;

		$index = $index - 1;
	}

	return $guestzone_mac;
}

function alpha_auth_to_bcm_akm($auth)
{
	if($auth == "WPA")
		return "wpa";
	else if($auth == "WPAPSK")
		return "psk";
	else if($auth == "WPA2")
		return "wpa2";
	else if($auth == "WPA2PSK")
		return "psk2";
	else if($auth == "WPA+2")
		return "wpa2";
	else if($auth == "WPA+2PSK")
		return "psk psk2";
	else
		return "";
}

function try_set_psk_passphrase($wl_prefix, $wifi)
{
	$auth = query($wifi."/authtype");
	if($auth != "WPAPSK" && $auth != "WPA2PSK" && $auth != "WPA+2PSK")
		return;

//	if(query($wifi."/nwkey/psk/passphrase") != "1")
//		return;

	$key = query($wifi."/nwkey/psk/key");
	echo "nvram set ".$wl_prefix."_wpa_psk=\"".$key."\"\n";
}

function alpha_enc_to_bcm_crypto($enc)
{
	if($enc == "TKIP")
		return "tkip";
	else if($enc == "AES")
		return "aes";
	else if($enc == "TKIP+AES")
		return "tkip+aes";
	else
		return "";
}

function repeater_security_setup($wl_prefix , $wifi)
{
	echo "nvram set ".$wl_prefix."_akm=\"".alpha_auth_to_bcm_akm(query($wifi."/authtype"))."\"\n";
	echo "nvram set ".$wl_prefix."_crypto=\"".alpha_enc_to_bcm_crypto(query($wifi."/encrtype"))."\"\n";
	try_set_psk_passphrase($wl_prefix, $wifi);
}

function clean_nvram_values($wl_prefix , $keys)
{
	$value_qty = cut_count($keys , " ");
	$index = 0;

	if($wl_prefix != "")
		$wl_prefix = $wl_prefix."_";

	while($index < $value_qty)
	{
		$key = cut($keys , $index , " ");
		if($key != "")
			echo "nvram unset ".$wl_prefix.$key."\n";

		$index += 1;
	}
}

function security_setup($PHY_UID, $wl_prefix, $wifi)
{
	echo "#wireless security setup start ---\n";

	echo "nvram set ".$wl_prefix."_auth_mode=none\n";
	echo "nvram set ".$wl_prefix."_auth=0\n";
	echo "nvram set ".$wl_prefix."_wep=disabled\n";
	/* some stuff about wpa. we don't need this setting, just clean it (tom, 20120406) */
	clean_nvram_values($wl_prefix , "akm");

	/* authtype */
	$auth = query($wifi."/authtype");
	/* encrtype */
	$encrypt = query($wifi."/encrtype");

	if ( $auth == "OPEN" || $auth == "SHARED" || $auth == "WEPAUTO")
	{
		/* be careful! help message of wl says open/share is value 3, but driver uses 2 (tom, 20120410) */
		if ( $auth == "SHARED" )	
		{
			echo "nvram set ".$wl_prefix."_auth=1\n";
		}
		else if ( $auth == "WEPAUTO" ) 
		{
			echo "nvram set ".$wl_prefix."_auth=2\n";
		}
		else						
		{
			echo "nvram set ".$wl_prefix."_auth=0\n";
		}

		if ( $encrypt == "WEP" )
		{
			echo "nvram set ".$wl_prefix."_wep=enabled\n";
			/* Now the wep key must be hex number, so using "query" is ok. */
			$defkey = query($wifi."/nwkey/wep/defkey");
			$keystring = query($wifi."/nwkey/wep/key:".$defkey);
			echo "nvram set ".$wl_prefix."_key=".$defkey."\n";
			echo "nvram set ".$wl_prefix."_key".$defkey."=\"".$keystring."\"\n";
		}
		else
		{
			echo "nvram set ".$wl_prefix."_wep=disabled\n";
		}
	}

	if( $auth=="WPA2" || $auth=="WPA" || $auth=="WPA+2")
	{
		echo "nvram set ".$wl_prefix."_preauth=0\n";
	}

	if(is_repeater_mode($PHY_UID) == 1)
		repeater_security_setup($wl_prefix , $wifi);

	echo "#wireless security setup end ---\n";
}

function checking_bandwidth($wlif_is_5g, $bandwidth, $nmode)
{
	if($bandwidth == "20" || $nmode == "0")
		return "20";
	else if($bandwidth == "20+40")
		return "40";
	else if($bandwidth == "20+40+80" && $wlif_is_5g == 1)	
		return "80";
	else if($bandwidth == "20+40+80" && $wlif_is_5g != 1)	
		return "40";		

	echo "#check bandwidth setting, we cannot find correct bandwidth (rtcfg.php)\n";
	return "20";
}

function control_sideband($wlif_is_5g, $bandwidth, $channel)
{
	/* how to get sideband list? just use "wl -i ifname -b <2|5> -w <20|40|80>" */
	if($channel == "0" || $bandwidth == "20")
		return "";

	/* for 5g, we don't need sideband setting, wlconf will do this for us */
	if($wlif_is_5g != 1)
	{
		/* for 2.4g */
		if($channel >=5)
			return "u";
		else
			return "l";
	}

	return "";
}

function channel_idx_to_channel_number_5g($idx)
{
	$path_a = query("/runtime/freqrule/channellist/a");
	if($path_a=="")
	{
		return 36;
	}
	
	$cnt = cut_count($path_a, ",");
	if($cnt != 0)
	{
		$idx = $idx % $cnt;
		$token = cut($path_a, $idx, ",");
	}
	
	if($token == "")
	{
		$token = 36;
	}
	return $token;
}

function channel_idx_to_channel_number_24g($idx)
{
	$path_g = query("/runtime/freqrule/channellist/g");
	
	if($path_g=="")
	{
		return 6;
	}
	$cnt = cut_count($path_g, ",");
	if($cnt != 0)
	{
		$idx = $idx % $cnt;
		$token = cut($path_g, $idx, ",");
	}

	if($token == "")
	{
		$token = 6;
	}
	return 	$token;
}

function bandwidth_adjust_for_TW($bandwidth, $channel)
{
	if(get("x", "/runtime/devdata/countrycode") != "TW")
		return $bandwidth; //we don't touch it

	//for band 1: 52, 56, 60, 64
	//HT80 must be: (52, 56, 60, 64)
	//HT40 must be: (52, 56), (60, 64)
	//if we remove 52, we need to downgrade bandwidth for some channels (tom, 20131009)
	if($bandwidth == "80")
	{
		if($channel == "56")
			return "20";

		if($channel == "60" || $channel == "64")
			return "40";
	}

	if($bandwidth == "40")
	{
		if($channel == "56")
			return "20";
	}

	return $bandwidth;
}

function channel_bandwidth_setup($wlif_bss_idx, $wlif_is_5g, $bandwidth, $nmode, $channel)
{
	echo "#wireless channel/bandwidth setup start ---\n";

	/* use new channel spec to config, so we clean this */
	echo "nvram set wl".$wlif_bss_idx."_channel=0\n";

	// get max bandwidth
	$bandwidth = checking_bandwidth($wlif_is_5g, $bandwidth, $nmode);

	$bandwidth = bandwidth_adjust_for_TW($bandwidth, $channel);

	if($bandwidth == "20")
		echo "nvram set wl".$wlif_bss_idx."_bw_cap=1\n";
	else if($bandwidth == "40")
		echo "nvram set wl".$wlif_bss_idx."_bw_cap=3\n";
	else if($bandwidth == "80")
		echo "nvram set wl".$wlif_bss_idx."_bw_cap=7\n";

	if($channel != 0)
	{
		$sideband = control_sideband($wlif_is_5g, $bandwidth, $channel);
		if($wlif_is_5g == 1)
			$channel_spec = "5g";
		else
			$channel_spec = "2g";

		$channel_spec = $channel_spec.$channel."/".$bandwidth.$sideband;

		echo "nvram set wl".$wlif_bss_idx."_chanspec=".$channel_spec."\n";
	}
	else
	{
		echo "nvram set wl".$wlif_bss_idx."_chanspec=0\n";
		if($wlif_is_5g==1)
		{
			$ifname = "wifia0";
		}
		else
		{
			$ifname = "wifig0";
		}

		//acsd will use this value do auto-channel
		echo "nvram set acs_ifnames=".$ifname."\n";
	}

	echo "#wireless channel/bandwidth setup end ---\n";
}

function test_override($wlif_bss_idx , $wlif_is_5g)
{
	echo "#test override start ---\n";

	/* those values come from broadcom's SDK firmware */
	//echo "nvram set wl".$wlif_bss_idx."_rxchain_pwrsave_enable=0\n";

	/* just for throughput test */
	echo "echo 300 > /proc/sys/net/core/netdev_budget\n";
	echo "echo 1000 > /proc/sys/net/core/netdev_max_backlog\n";

	echo "#test override end ---\n";
}

function dev_default_values($PHY_UID, $wl_prefix)
{
	$guestzone = isguestzone($PHY_UID);

	echo "#defaule values start ---\n";

	if ($guestzone == 1)
	{
		//guest zone default setting here
		return;
	}

	//master default setting here
	echo "nvram set ".$wl_prefix."_mimo_preamble=gfbcm\n";
	echo "nvram unset ".$wl_prefix."_nmode_protection\n";
	echo "nvram unset ".$wl_prefix."_gmode_protection\n";
	echo "nvram unset ".$wl_prefix."_nmode\n";
	echo "nvram unset ".$wl_prefix."_gmode\n";

	echo "#default values end ---\n";
}

function get_ure_disable()
{
	$index = 0;
	while($index < 2)
	{
		$sta_enabled = query("/runtime/wifi/wl".$index."/sta");
		if($sta_enabled == "1")
			return "0";

		$index += 1;
	}

	return "1";
}

function operation_mode_setup($PHY_UID, $wl_prefix, $wifi)
{
	echo "#operation mode setup start ---\n";

	clean_nvram_values($wl_prefix , "sta_retry_time wps_mode wps_oob ure");
	
	if(is_repeater_sta($PHY_UID) == 1)
	{
		echo "nvram set ".$wl_prefix."_mode=psta\n";
		echo "nvram set ".$wl_prefix."_sta_retry_time=5\n";
		clean_nvram_values("" , "wan_ifnames lan_ifname");

		//those values are used by nas and eapd daemons
		echo "nvram set lan_ifname=\"".devname($PHY_UID)."\"\n";
		echo "nvram set lan_ifnames=\"".$wl_prefix."\"\n";
		echo "nvram set ".$wl_prefix."_ifname=".devname($PHY_UID)."\n";
	}
	else if(is_repeater_ap($PHY_UID) == 1)
	{
		$parent_uid = get_parent_phy_uid($PHY_UID);
		//set proxy station repeater mode
		echo 'nvram set wl'.get_wlif_bss($parent_uid).'_mode=psr\n';
		//echo 'nvram set wl'.get_wlif_bss($parent_uid).'_vifs='.$wl_prefix.'\n';
		echo 'nvram set wl'.get_wlif_bss($parent_uid).'_vifs='.devname($PHY_UID).'\n';
		echo "nvram set ".$wl_prefix."_mode=ap\n";
		echo "nvram set lan_ifnames=\"wl".get_wlif_bss($parent_uid)." ".devname($PHY_UID)."\"\n";
	}
	else
	{
		echo "nvram set ".$wl_prefix."_mode=ap\n";
	}

	/*
		When smart connect enable and WAN VLAN enable, we need to change nvram setting(lan ifname).
		Because wifi interface will be added to bridge(br2: voip, br3: iptv).
	*/
	$smart_en = query("/device/features/smartconnect");
	$vlan_en = query("/device/vlan/active");
	$br = "br0";
	if($smart_en=="1" && $vlan_en=="1")
	{
		$wlan_vid = query("/device/vlan/wlanport/wlan01");
		$voipid = query("/device/vlan/voipid");
		$iptvid = query("/device/vlan/iptvid");
		if($wlan_vid == $voipid)
			$br = "br2";
		else if($wlan_vid == $iptvid)
			$br = "br3";
	}

	$ifname = "wl1 wl1.1 wl0 wl0.1 ";
	echo "nvram set lan_ifname=\"".$br."\"\n";
	echo "nvram set lan_ifnames=\"".$ifname."\"\n";
	echo "nvram set ".$br."_ifname=\"".$br."\"\n";
	$ifname = "wl1 wl1.1 wl0 wl0.1 ";	
	echo "nvram set ".$br."_ifnames=\"".$ifname."\"\n";

	echo "nvram set ure_disable=1\n";

	echo "#operation mode setup end ---\n";
}

function set_mac_address($uid)
{
	echo "#set mac address start ---\n";
	$mac_address = "";

	if(isband5g($uid) == 0)
	{
		$mac_address = PHYINF_getdevdatamac("wlanmac");
	}
	else
	{
		$mac_address = PHYINF_getdevdatamac("wlanmac2");
	}

	if(is_repeater_ap($uid) != 1)
	{
		//in broadcom's repeater mode, ap interface has the same MAC with station interface
		if(isguestzone($uid) != 0)
		{
			$mac_address = get_guestzone_mac($mac_address);	
		}
	}

	$wlif_bss_idx = get_wlif_bss($uid);
	echo "nvram set wl".$wlif_bss_idx."_hwaddr=".$mac_address."\n";

	echo "#set mac address end ---\n";
}

function set_wireless_phy_mode($uid)
{
	echo "#set wireless phy mode start ---\n";

	$wlif_bss_idx = get_wlif_bss($uid);

	if(isband5g($uid) != 0)
	{
        /* set phytype to PHY_TYPE_AC */
        echo "nvram set wl".$wlif_bss_idx."_phytype=v\n";
        /* 0: Auto, 1: A band, 2: G band, 3: All */
        echo "nvram set wl".$wlif_bss_idx."_nband=1\n";
        /* 802.11h, 5G we use it (for dfs)
        /* Regulatory Mode:off(disabled), h(802.11h), d(802.11d)*/
        echo "nvram set wl".$wlif_bss_idx."_reg_mode=h\n";
	}
	else
	{
        /* set phytype to PHY_TYPE_HT */
        echo "nvram set wl".$wlif_bss_idx."_phytype=h\n";
        /* 0: Auto, 1: A band, 2: G band, 3: All */
        echo "nvram set wl".$wlif_bss_idx."_nband=2\n";
        /* 802.11h, we didn't use it for 2.4G , so disable it. If it enabled, the IE will contain coutry code.*/
        /* Regulatory Mode:off(disabled), h(802.11h), d(802.11d)*/
        echo "nvram set wl".$wlif_bss_idx."_reg_mode=off\n";
	}

	echo "nvram set wl".$wlif_bss_idx."_rateset=default\n";
	echo "#set wireless phy mode end ---\n";
}

function set_wireless_acl_mode($wlif_bss_idx, $wifi)
{
	echo "#set wireless acl mode start ---\n";

	/* aclmode 0:disable, 1:allow all of the list, 2:deny all of the list */
    $aclmode = query($wifi."/acl/policy");
    if($aclmode == "ACCEPT" ) 
	{ 
		$ACLMODE_CMD="allow"; 
		$aclmode=1; 
	}
    else if ($aclmode == "DROP" )
	{ 
		$ACLMODE_CMD="deny";  
		$aclmode=2; 
	}
    else                    
	{ 
		$ACLMODE_CMD="disabled"; 
		$aclmode=0; 
	}

    echo "nvram set wl".$wlif_bss_idx."_macmode=".$ACLMODE_CMD."\n";

    if ($aclmode > 0)
    {
        $acl_count = query($wifi."/acl/count");
        $acl_max = query($wifi."/acl/max");
        $acl_list = "";
        foreach($wifi."/acl/entry")
        {
            if ($InDeX > $acl_count || $InDeX > $acl_max) break;

            if ($acl_list!="")
			{
				$acl_list = $acl_list." ".query("mac");
			}
            else
			{               
				$acl_list = query("mac");
			}
        }
        if ($acl_list!="")  echo "nvram set wl".$wlif_bss_idx."_maclist=".$acl_list."\n";
    }

	echo "#set wireless acl mode end ---\n";
}

function set_bss_cap_restriction($wlif_bss_idx, $wireless_mode)
{
	echo "#set bss cap restriction start ---\n";
	if($wireless_mode == "n" || $wireless_mode == "acn")
	{
		//if 802.11n only, shall set wlx_bss_opmode_cap_reqd=2 to limit only 11N STA can connect
        echo "nvram set wl".$wlif_bss_idx."_bss_opmode_cap_reqd=2\n";
	}
	else if($wireless_mode == "ac")
	{
		//if 802.11ac only, shall set wlx_bss_opmode_cap_reqd=3 to limit only 11AC STA can connect
        echo "nvram set wl".$wlif_bss_idx."_bss_opmode_cap_reqd=3\n";
	}
	else
	{
        echo "nvram set wl".$wlif_bss_idx."_bss_opmode_cap_reqd=0\n";
	}

	echo "#set bss cap restriction end ---\n";
}

function set_n_mcs($wlif_bss_idx, $wireless_mode, $mcs_idx)
{
	echo "#set n mcs start ---\n";

	//default is auto
	echo "nvram set wl".$wlif_bss_idx."_nmcsidx=-1\n";

	if($mcs_idx != "-1")
	{
		if($wireless_mode == "n")
		{
			echo "nvram set wl".$wlif_bss_idx."_nmcsidx=".$mcs_idx."\n";
		}
	}

	echo "#set n mcs end ---\n";
}

function config_flags_gn($wlif_bss_idx, $wifi, $wlmode)
{
	echo "#config N and G flags start ---\n";

    if      ($wlmode == "a")		{$gmode="0"; $nmode="0";}
    else if ($wlmode == "an")		{$gmode="0"; $nmode="1";}
    else if ($wlmode == "bgn")		{$gmode="1"; $nmode="1";}
    else if ($wlmode == "bg")		{$gmode="1"; $nmode="0";}
    else if ($wlmode == "n")		{$gmode="0"; $nmode="1";}
    else if ($wlmode == "g")		{$gmode="2"; $nmode="0";}
    else if ($wlmode == "b")		{$gmode="0"; $nmode="0";}
    else if ($wlmode == "ac")		{$gmode="0"; $nmode="1";}
    else if ($wlmode == "acn")		{$gmode="0"; $nmode="1";}
    else if ($wlmode == "acna")		{$gmode="0"; $nmode="1";}
    else
    {  
        /* use 'bgn' as default.*/
        TRACE_info("rtcfg (broadcom conf): Not supported wireless mode: [".$wlmode."].Use 'bng' as default wireless mode.");
        $gmode="1"; $nmode="1";
    }

	$authtype = query($wifi."/authtype");
	$encrtype = query($wifi."/encrtype");

	if($authtype=="OPEN"|| $authtype=="SHARED" ||$authtype=="WEPAUTO")
	{   
	    if($encrtype=="WEP")
    	{  
			$nmode = 0;
		}
	}

	if($authtype=="WPAPSK"|| $authtype=="WPA2PSK" ||$authtype=="WPA+2PSK")
	{   
		if($encrtype=="TKIP")
		{
			$nmode = 0;
		}
	}

	echo "nvram set wl".$wlif_bss_idx."_gmode=".$gmode."\n";
	echo "nvram set wl".$wlif_bss_idx."_nmode=".$nmode."\n";

	echo "#config N and G flags end ---\n";
}

function config_obss_coex($wlif_bss_idx, $phy, $wlif_is_5g)
{
	echo "#config obss coex start ---\n";
    /* obss_coex, only 2.4G has this */
    if($wlif_is_5g == 1)
    {
        echo "nvram unset wl".$wlif_bss_idx."_obss_coex\n";
    }
    else
    {
        if(query($phy."/media/dot11n/bw2040coexist") == "1")
            echo "nvram set wl".$wlif_bss_idx."_obss_coex=1\n";
        else
            echo "nvram set wl".$wlif_bss_idx."_obss_coex=0\n";
    }
	echo "#config obss coex end ---\n";
}

function dev_start($PHY_UID, $pci_2g_path, $pci_5g_path)
{
	$phy	= XNODE_getpathbytarget("",			"phyinf", "uid", $PHY_UID);
	$phyrp  = XNODE_getpathbytarget("/runtime",     "phyinf", "uid", $PHY_UID);
	$wifi	= XNODE_getpathbytarget("/wifi",	"entry",  "uid", query($phy."/wifi"));
	$winf   = query($phyrp."/name");
	$brphyinf   = find_brdev($PHY_UID);
	$guestzone = isguestzone($PHY_UID);

	$wlif_is_5g = isband5g($PHY_UID);
	$wlif_bss_idx = get_wlif_bss($PHY_UID);

	echo "#PHY_UID == ".$PHY_UID."\n";
	echo "#phy == ".$phy."\n";
	echo "#wifi == ".$wifi."\n";
	echo "#brphyinf == ".$brphyinf."\n";

	if ($guestzone == 1)
	{
		$wl_prefix=$winf;
	} else {
		$wl_prefix="wl".$wlif_bss_idx;
	}

	dev_default_values($PHY_UID, $wl_prefix);

	/* Start the master */
	echo "nvram set ".$wl_prefix."_ssid=\"".get("s",$wifi."/ssid")."\"\n";
	echo "nvram set ".$wl_prefix."_closed=0\n";
	echo "nvram set ".$wl_prefix."_bss_enabled=1\n";

	/* Broadcom driver will read following parameters when WPA is enabled */
	echo "nvram set ".$wl_prefix."_radio=1\n";
	echo "nvram set ".$wl_prefix."_unit=".$wlif_bss_idx."\n";
	echo "nvram set ".$wl_prefix."_maxassoc=128\n";
	echo "nvram set ".$wl_prefix."_bss_maxassoc=128\n";

	// set mode ap or station
	operation_mode_setup($PHY_UID, $wl_prefix, $wifi);

	security_setup($PHY_UID, $wl_prefix, $wifi);

	if(is_repeater_ap($PHY_UID) == 1)
	{
		//because eapd and nas use wlx.x nvram values, we needs write them again
		operation_mode_setup($PHY_UID, "wl".$wlif_bss_idx, $wifi);
		security_setup($PHY_UID, "wl".$wlif_bss_idx, $wifi);

		//because eapd and nas use wlx.x nvram values, we needs write them again
		echo "nvram set wl".$wlif_bss_idx."_ssid=\"".get("s",$wifi."/ssid")."\"\n";
		echo "nvram set wl".$wlif_bss_idx."_closed=0\n";
		echo "nvram set wl".$wlif_bss_idx."_bss_enabled=1\n";

		echo "nvram set wl".$wlif_bss_idx."_radio=1\n";
		echo "nvram set wl".$wlif_bss_idx."_unit=".$wlif_bss_idx."\n";
		echo "nvram set wl".$wlif_bss_idx."_maxassoc=128\n";
		echo "nvram set wl".$wlif_bss_idx."_bss_maxassoc=128\n";
	}

	set_mac_address($PHY_UID);

	if($guestzone == 1)
	{
		// HuanYao Kang: eapd needs this.
		/*echo "nvram set wl".$wlif_bss_idx."_radio=1\n"; 
		echo "nvram set wl".$wlif_bss_idx."_closed=0\n";
		echo "nvram set wl".$wlif_bss_idx."_bss_enabled=1\n";
		operation_mode_setup($PHY_UID, "wl".$wlif_bss_idx, $wifi);
		security_setup($PHY_UID, "wl".$wlif_bss_idx, $wifi);*/
		
		if(is_repeater_ap($PHY_UID) == 1)
		{
			/*echo "nvram set wl".$wlif_bss_idx."_unit=".$wlif_bss_idx."\n";
			echo "nvram set wl".$wlif_bss_idx."_maxassoc=128\n";
			echo "nvram set wl".$wlif_bss_idx."_bss_maxassoc=128\n";*/
		}
		//finish for guest interface
		return 0;
	}

	set_wireless_phy_mode($PHY_UID);

	/* Get configuration */
	anchor($phy."/media");
	$channel		= query("channel");
	$autochannel	= query("autochannel");			if ($autochannel=="1")		{$channel="0";}
	$beaconinterval	= query("beacon");				if ($beaconinterval=="")	{$beaconinterval="100";}
	$fraglength		= query("fragthresh");			if ($fraglength=="")		{$fraglength="2346";}
	$rtslength		= query("rtsthresh");			if ($rtslength=="")			{$rtslength="2346";}
	$ssidhidden		= query($wifi."/ssidhidden");			if ($ssidhidden!="1")		{$ssidhidden="0";}

	//$ctsmode		= query("ctsmode");				if ($ctsmode=="")			{$ctsmode="0";}
	$preamble		= query("preamble");			if ($preamble=="")			{$preamble="long";}
	$txrate			= query("txrate");
	$txpower		= query("txpower");
	$dtim			= query("dtim");				if ($dtim=="")				{$dtim="1";}
	$wlan2wlan		= query("bridge/wlan2wlan");	if ($wlan2wlan!="0")		{$wlan2wlan="1";}
	$wlan2lan		= query("bridge/wlan2lan");		if ($wlan2lan!="0")			{$wlan2lan="1";}
	$bandwidth		= query("dot11n/bandwidth");

	$mcs_auto		= query("dot11n/mcs/auto");			
	$mcs_idx		= query("dot11n/mcs/index");			
	if ( $mcs_auto != 1 && $mcs_idx == "" )		{ $mcs_auto = 1;	}

	/* set short quard interval - this nvram var was created by ALPHA , and code at wlconf */
	$short_guardintv		= query("dot11n/guardinterval");			
	if ($short_guardintv=="400" )			{$short_guardintv="1";}
	else		{$short_guardintv="0";}


	if ($txrate == "5.5") { $TXRATE_CMD="5500000"; } 
	else if ($txrate == "auto") { $TXRATE_CMD=0; } 
	else { $TXRATE_CMD = $txrate * 1000000; }

	/* /wireless/wlanmode : 
	*  2.4G	1:11b, 2:11g, 3:11b+11g, 4:11n, 5:11g+11n, 6: 11b+11g+11n, 
	*    5G 7:a, 8:n, 9:a+n, 10:ac, 11:n+ac, 12:a+n+ac
	*/
	$wireless_mode = query($phy."/media/wlmode");

	/* For preamble: default is long preamble. */
	echo "nvram set wl".$wlif_bss_idx."_plcphdr=".$preamble."\n";

	config_flags_gn($wlif_bss_idx, $wifi, $wireless_mode);

	config_obss_coex($wlif_bss_idx, $phy, $wlif_is_5g);

	echo "nvram set wl".$wlif_bss_idx."_frameburst=on\n";	

	if($mcs_auto == 1)
	{
		set_n_mcs($wlif_bss_idx, $wireless_mode, "-1");
	}
	else
	{
		set_n_mcs($wlif_bss_idx, $wireless_mode, $mcs_idx);
	}

	set_bss_cap_restriction($wlif_bss_idx, $wireless_mode);

	/* generic settings ____________________________________________________ */
	$wmm = query("wmm/enable");                  
	if($wmm != "1")
	{
		$wmm="0";
	}

	echo "nvram set wl".$wlif_bss_idx."_wme_bss_disable=0\n";
	echo "nvram set wl".$wlif_bss_idx."_wme=";
	if ($wmm==1) { echo "on"; } else { echo "off"; }
	echo "\n";

	/* Wifi-WMM parameters */
	wmm_paramters( $wlif_bss_idx );

	echo "nvram set wl".$wlif_bss_idx."_bcn=".$beaconinterval."\n";	
	echo "nvram set wl".$wlif_bss_idx."_dtim=".$dtim."\n";

	echo "nvram set wl".$wlif_bss_idx."_closed=".$ssidhidden."\n";

	echo "nvram set wl".$wlif_bss_idx."_rts=".$rtslength."\n";

	echo "nvram set wl".$wlif_bss_idx."_frag=".$fraglength."\n";
	echo "nvram set wl".$wlif_bss_idx."_rate=".$TXRATE_CMD."\n";

	if ( $wmm =="1" )	{	echo "et qos 1\n";	}
	else				{	echo "et qos 0\n";	}
	
	if(is_repeater_sta($PHY_UID) == 1)
	{
		//just set it to max bw, channel_bandwidth_setup can fix it
		$bandwidth = "20+40+80";
	}

	channel_bandwidth_setup($wlif_bss_idx, $wlif_is_5g, $bandwidth, $nmode, $channel);

	/* set short quard interval - this nvram var was created by ALPHA , and code at wlconf */
	echo "nvram set wl".$wlif_bss_idx."_short_sgi_enable=".$short_guardintv."\n";	

	/* WLAN/LAN bridge _____________________________________________________ */
	echo "nvram set wl".$wlif_bss_idx."_ap_isolate=";
	$isolate = query($wifi."/acl/isolation");
	if ($isolate == "0") { echo "0\n"; } else { echo "1\n"; }

	set_wireless_acl_mode($wlif_bss_idx, $wifi);

	$wps_en=query("/runtime/wps_sta/enable");
	if($wps_en=="1")
	{
		echo "nvram set ".$wl_prefix."_mode=wet\n";
		set_sta_mode($wl_prefix , "1");
		echo "nvram set ".$wl_prefix."_ure=1\n";
		echo "nvram unset ".$wl_prefix."_ssid\n";
		echo "nvram set ".$wl_prefix."_wps_mode=enabled\n";
		echo "nvram set ".$wl_prefix."_wps_mode=enabled\n";
		echo "nvram set wps_pbc_apsta=enabled\n";
		echo "nvram set wps_method=2\n";
	}
	test_override($wlif_bss_idx , $wlif_is_5g);
}

/**********************************************************************************/

echo "#!/bin/sh\n";

if ($ACTION=="START")
{
	dev_start($PHY_UID, $PCI_2G_PATH, $PCI_5G_PATH);
} else if ($ACTION=="STOP") 
{
	dev_stop($PHY_UID);
} else if ($ACTION=="INIT") 
{
	dev_init($PCI_2G_PATH, $PCI_5G_PATH);
}

?>
