<?
if(exist("/runtime/devdata/flashspeed")==1)
{
	$flashspeed = get("", "/runtime/devdata/flashspeed");
	del("/runtime/devdata/flashspeed");
	set("/runtime/devdata/flashspeed", $flashspeed);
}
if(exist("/runtime/devdata/pin")==1)
{
	$pin = get("", "/runtime/devdata/pin");
	del("/runtime/devdata/pin");
	set("/runtime/devdata/pin", $pin);
}
if(exist("/runtime/devdata/psk")==1)
{
	$psk = get("", "/runtime/devdata/psk");
	del("/runtime/devdata/psk");
	set("/runtime/devdata/psk", $psk);
}
if(exist("/runtime/devdata/hwver")==1)
{
	$hwver = get("", "/runtime/devdata/hwver");
	del("/runtime/devdata/hwver");
	set("/runtime/devdata/hwver", $hwver);
}
if(exist("/runtime/devdata/countrycode")==1)
{
	$countrycode = get("", "/runtime/devdata/countrycode");
	del("/runtime/devdata/countrycode");
	set("/runtime/devdata/countrycode", $countrycode);
}
if(exist("/runtime/devdata/wanmac")==1)
{
	$wanmac = get("", "/runtime/devdata/wanmac");
	del("/runtime/devdata/wanmac");
	set("/runtime/devdata/wanmac", $wanmac);
}
if(exist("/runtime/devdata/lanmac")==1)
{
	$lanmac = get("", "/runtime/devdata/lanmac");
	del("/runtime/devdata/lanmac");
	set("/runtime/devdata/lanmac", $lanmac);
}
if(exist("/runtime/devdata/wlanmac")==1)
{
	$wlanmac = get("", "/runtime/devdata/wlanmac");
	del("/runtime/devdata/wlanmac");
	set("/runtime/devdata/wlanmac", $wlanmac);
}
if(exist("/runtime/devdata/wlan5mac")==1)
{
	$wlan5mac = get("", "/runtime/devdata/wlan5mac");
	del("/runtime/devdata/wlan5mac");
	set("/runtime/devdata/wlan5mac", $wlan5mac);
}
if(exist("/runtime/devdata/wlanmac2")==1)
{
	$wlanmac2 = get("", "/runtime/devdata/wlanmac2");
	del("/runtime/devdata/wlanmac2");
	set("/runtime/devdata/wlanmac2", $wlanmac2);
}
if(exist("/runtime/devdata/lanpack")==1)
{
	$lanpack = get("", "/runtime/devdata/lanpack");
	del("/runtime/devdata/lanpack");
	set("/runtime/devdata/lanpack", $lanpack);
}
if(exist("/runtime/devdata/mfcmode")==1)
{
	$mfcmode = get("", "/runtime/devdata/mfcmode");
	del("/runtime/devdata/mfcmode");
	set("/runtime/devdata/mfcmode", $mfcmode);
}
?>
