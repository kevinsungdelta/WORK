<?
/*rework wifi to
1.ssid dlink+mac
2.password and wpaauto psk.
*/
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/webinc/config.php";

setattr("/runtime/devdata/wifiverify" ,"get","devdata get -e wifiverify");
setattr("/runtime/devdata/ipv6logo" ,"get","devdata get -e ipv6logo");

setattr("/runtime/tmpdevdata/wifipassword" ,"get","devdata get -e psk"); 
setattr("/runtime/tmpdevdata/ssid_2G" ,"get","devdata get -e wifissid_2g"); 
setattr("/runtime/tmpdevdata/ssid_5G" ,"get","devdata get -e wifissid_5g"); 
setattr("/runtime/tmpdevdata/wlanmac" ,"get","devdata get -e wlan24mac");
setattr("/runtime/tmpdevdata/wlanmac_sb" ,"get","devdata get -e wlanmac");
setattr("/runtime/tmpdevdata/wlanmac2" ,"get","devdata get -e wlan5mac"); 
setattr("/runtime/tmpdevdata/lanmac" ,"get","devdata get -e lanmac"); 
setattr("/runtime/tmpdevdata/countrycode" ,"get","devdata get -e countrycode");
setattr("/runtime/tmpdevdata/is_freset" ,"get","mfc isfreset");

function changes_default_wifi($phyinfuid,$ssid,$password,$mac,$country)
{
	$authtype = "WPA+2PSK";
	$encrtype = "TKIP+AES";	
	
	$p = XNODE_getpathbytarget("", "phyinf", "uid", $phyinfuid, 0);
	$wifi = XNODE_getpathbytarget("/wifi", "entry", "uid", query($p."/wifi"), 0);
	if($p=="" || $wifi=="")
	{
		return;	
	}
	anchor($wifi); 
	
	if($ssid=="")
	{
		$n5 = cut($mac, 4, ":");
		$n6 = cut($mac, 5, ":");
		$ssidsuffix = $n5.$n6;
		$ssid		= query("ssid");
		if($ssidsuffix!="")
		{		
			//For dlink product, make the default SSID "dlink-mac" for 2.4GHz and "dlink-mac-media" for 5GHz.
			if(substr($ssid, 0, 5) == "dlink")
			{
				$ssid1	= "dlink";
				$ssid2	= scut($ssid, 0, "dlink");
				$ssid	= $ssid1."-".toupper($ssidsuffix).$ssid2;
			}
		}
	}
	
	//set default value for CN
	if($country=="CN")
	{
		$authtype = "OPEN";
		$encrtype = "NONE";
		set("authtype",$authtype);
		set("encrtype",$encrtype);
        set("ssid",$ssid);
		set("nwkey/psk/key","");
		set("wps/configured","1");
		return;
	}
 
	//TRACE_error("ssid=".$ssid."and password=".$password."");
	if($password!="" && $ssid!="")
	{
		//change the mode to wpa-auto psk
		set("authtype",$authtype);
		set("encrtype",$encrtype);
		set("wps/configured","1");		
		set("ssid",$ssid);		
		set("nwkey/psk/passphrase","1");
		set("nwkey/psk/key",$password);
		set("nwkey/wpa/groupintv","3600");
		set("nwkey/rekey/gtk","1800");
	}
	else
	{
		TRACE_error("the mfc do not init wifi password,using default");
	}
	/*
	if(query("/runtime/devdata/wifiverify")=="1" && query($p."/uid")=="BAND5G-1.1")
	{
		set($p."/media/wlmode","an");
		set($p."/media/dot11n/bandwidth","20+40");
	}
	*/
}

function changes_default_tz($country)
{
	if($country != "")
	{
		TRACE_debug("changes_default_tz(): country=".$country);
		if     ($country=="AU")		set("/device/time/timezone","67"); //set TZ Brisbane for Australia
		else if($country=="CA")		set("/device/time/timezone","14"); //set TZ Eastern Time (US & Canada) for Canada
		else if($country=="CN")		set("/device/time/timezone","57"); //set TZ Beijing for China
		else if($country=="SG")		set("/device/time/timezone","58"); //set TZ Singapore for Singapore
		else if($country=="TW")		set("/device/time/timezone","60"); //set TZ Taipei for Taiwan
		else if($country=="US" 
				 || $country=="NA")		set("/device/time/timezone","14"); //set TZ Eastern Time (US & Canada) for United States
		else if($country=="LA")		set("/device/time/timezone","11"); //set TZ Mexico City for Latin America
		else if($country=="EU" 
				 || $country=="GB")		set("/device/time/timezone","28"); //set TZ for Denmark, Germany, Iceland,Finland, Netherlands, Norway,Sweden, Poland, Slovenia,Luxembourg, South Africa,UK,Ireland
		else if($country=="IL")		set("/device/time/timezone","38"); //set TZ Jerusalem for Israel
		else if($country=="KR")		set("/device/time/timezone","64"); //set TZ Seoul for Korea
		else if($country=="JP")		set("/device/time/timezone","63"); //set TZ Tokyo for Japan
		else if($country=="EG")		set("/device/time/timezone","35"); //set TZ Cairo for Egypt
		else if($country=="BR")		set("/device/time/timezone","20"); //set TZ Brasilia for Brazil
		else if($country=="RU")		set("/device/time/timezone","45"); //set TZ Moscow for Russia	
		else if($country=="DI")		set("/device/time/timezone","11"); //set TZ for Countries which uses FCC cetification, like Latin America, Brazil, Peru, Colombia,Chile, Argentina
		else											TRACE_error("changes_default_tz(): Error unknow country code.");
	}
}

$password = query("/runtime/tmpdevdata/wifipassword");
$wifiverify = query("/runtime/devdata/wifiverify");
if($wifiverify=="1") $password = "";

$ssid_2G = query("/runtime/tmpdevdata/ssid_2G");
$ssid_5G = query("/runtime/tmpdevdata/ssid_5G");
$lanmac = query("/runtime/tmpdevdata/lanmac");

// fw query need this value
if(query("/runtime/tmpdevdata/is_freset") == "YES" && $FEATURE_FACEBOOK == 1)
{
	set("/device/fwcheckparameter","Ax_FB");	
}

/* set default time zone by countrycode, Sammy */
$country = get("","/runtime/tmpdevdata/countrycode");
changes_default_tz($country);

changes_default_wifi($WLAN1,$ssid_2G,$password,$lanmac,$country);
changes_default_wifi($WLAN2,$ssid_5G,$password,$lanmac,$country);

//set default value for CN
if($country=="CN")
{
	$wan_infp = XNODE_getpathbytarget("", "inf", "uid", $WAN1, 0);
	$wan_inet = get("",$wan_infp."/inet");
	$wan_inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $wan_inet, 0);
	set($wan_inetp."/addrtype","ppp4");	
	
	$phy = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1, 0);
	set($phy."/media/dot11n/bw2040coexist","0");	
}

/*remove links*/
del("/runtime/tmpdevdata");
?>

