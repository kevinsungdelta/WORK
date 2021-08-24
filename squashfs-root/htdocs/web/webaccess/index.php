
<html>
	<head>
		<title>D-LINK</title>
		<meta http-equiv="X-UA-Compatible" content="IE=9">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="Content-Type" content="text/css">
		<link rel="stylesheet" type="text/css" href="css/style_sharePort.css" media="all" />
		<link rel=stylesheet type="text/css" href="/css/jquery.selectbox.css" />
		<script type="text/javascript" src="js/jquery-1.8.2.min.js" tppabs="/webaccess/js/jquery-1.8.2.min.js"></script>
		<script type="text/javascript" src="js/jquery.selectbox-0.2_new.js" tppabs="/webaccess/js/jquery.selectbox-0.2_new.js"></script>
		<script type="text/javascript" src="fancybox/json2.js" tppabs="/webaccess/fancybox/json2.js"></script>
    <script language="JavaScript" src="js/object.js" tppabs="/webaccess/js/object.js"></script>
		<script language="JavaScript" src="js/xml.js" tppabs="/webaccess/js/xml.js"></script>
		<script language="JavaScript" src="js/public.js" tppabs="/webaccess/js/public.js"></script>
		<script language="JavaScript" src="js/md5.js" tppabs="/webaccess/js/md5.js"></script>
		<script language="JavaScript" src="js/libajax.js"></script>
		<script language="JavaScript" src="js/i18n.js"></script>
		<script type="text/javascript">
			// Auto Detecting Language, Timmy 2013/05/03, 2013/05/31(Modify)
			var SetLang = 0;
			var IsPrivateBrowseing = 0;

			try
			{
				// Try to set localstorage
				localStorage.setItem('Test', 'test');
				localStorage.removeItem('Test');

				// Language List
				var LangList = new Array("en-us", "zh-tw", "zh-cn", "ko-kr", "fr-fr", "pt-br", "es-es", "it-it", "de-de", "ru-ru");

				if (localStorage.getItem('language') === null)
				{
					if (navigator.appName == 'Netscape')
					{
						var language = navigator.language;
					}
					else
					{
						var language = navigator.browserLanguage;
					}

					var SetLanguage = language.toLowerCase();
					var IsSuccessSetupLang = "false";

					for (var i = 0; i < LangList.length; i ++)
					{
						if (SetLanguage == LangList[i])
						{
							localStorage.setItem('language', LangList[i]);
							localStorage.language = LangList[i];
							InitLANG(localStorage.language);
							IsSuccessSetupLang = "true";
							SetLang = i;
						}
					}
					//If the language of the browser is not in LangList array, use English to initial.
					if (localStorage.getItem('language') === null)	InitLANG("en-us");
				}
				else
				{
					InitLANG(localStorage.language);
					for (var i = 0; i < LangList.length; i ++)
					{
						if (localStorage.language == LangList[i])
						{
							SetLang = i;
						}
					}
				}
			}
			catch (error)
			{
				if (error.code === DOMException.QUOTA_EXCEEDED_ERR && localStorage.length === 0)
				{
					InitLANG("en-us");
					alert(I18N("j", "The Private Browsing feature of Safari is incompatible with this device's interface. You will need to disable Private Browsing to log in."));
					IsPrivateBrowseing = 1;
				}
				else throw error;
			}

			function switch_Language(value)
			{
				sessionStorage.removeItem('langPack');
				localStorage.setItem('language', LangList[value]);
				localStorage.language = LangList[value];
				location.reload();
			}
		</script>
		<script language="JavaScript">
			var my_xml;
			var media_info;

			load_lang_obj();	// you have to load language object for displaying words in each html page and load html object for the redirect or return page
			document.onkeypress = login_key_handler;  //click the "Enter" Key can also browse web gui

			function get_settings_xml(http_req){
				my_xml = http_req.responseXML;
				disable_all_btn(false);
			}

			function show_auth_fail(){
					alert(I18N("j", "User Name or Password is incorrect."));
					disable_all_btn(false);
			}

			function redirect_category_page(http_req){
				var my_txt = http_req.responseText;
				var resp_info;

				try {
					resp_info = JSON.parse(my_txt);
				} catch(e) {
					show_auth_fail();
					return;
				}

				if (resp_info.status == "ok" && resp_info.error == null){
					location.href = "category_view.php"/*tpa=/webaccess/category_view.php*/;
				}else if (resp_info.error == 5003){
					show_auth_fail();
				}else show_auth_fail();
			}

			function send_request(){
				var xml_request = new XMLRequest(redirect_category_page);
				var user_name = (get_by_id("user_name").value).toLowerCase();	// always make user name to lowercase
				var user_pwd = get_by_id("user_pwd").value;
				var digest;
				var para;
				digest = hex_hmac_md5(user_pwd, user_name + media_info.challenge);
				para = "id=" + user_name + "&password=" + digest;

				xml_request.exec_auth_cgi(para);
			}

			function get_auth_info_result(http_req){
				var my_txt = http_req.responseText;
				try {
					media_info = JSON.parse(my_txt);
				} catch(e) {
					show_auth_fail();
					return;
				}
					document.cookie	= "uid=" + media_info.uid+";path=/";
					send_request();
				}

			function get_auth_info(){
				var xml_request = new XMLRequest(get_auth_info_result);

				xml_request.exec_auth_cgi();
			}

			function login_key_handler(e){
				var which_key;

				if (document.all) {
					which_key = window.event.keyCode;
				}else{
					which_key = e.which;
				}

				if (which_key == 13){ //click the "Enter" Key can also browse web gui
					get_auth_info();
                                }
			}

			function get_system_info_result(http_req){
				my_xml = http_req.responseXML;
			}

			function get_system_info(){
				var xml_request = new XMLRequest(get_system_info_result);
				var para = "request=get_system_info";

				xml_request.exec_webfile_cgi(para);

				document.getElementById("Language").options[SetLang].selected = true;
				$("#Language").selectbox('detach');
				$("#Language").val(SetLang);
				$("#Language").selectbox({width:120});

				//Show the hyperlinks of device access if we make sure the IP is private IP.
				if(window.location.hostname=="shareport.local" || window.location.hostname.substr(0, 8)=="192.168." || window.location.hostname.substr(0, 3)=="10." || window.location.hostname.substr(0, 7)=="172.16.")
					document.getElementById("link").style.display = "";
			}
			
			function change_fontsize(){
				var lang_tmp = localStorage.language;
				
				if(lang_tmp == "fr-fr" || lang_tmp == "pt-br" || lang_tmp == "es-es" || lang_tmp == "ru-ru")
				{
					document.getElementById("Login_Username").style.fontSize = "11px";
					document.getElementById("Login_Password").style.fontSize = "11px";
				}
			}
			
			$(function () {
				$("#Language").selectbox({width:120});
				if (IsPrivateBrowseing == 1)	{
					$("#Language").selectbox("disable");
				}
			});
   	</script>
	</head>
	<!--body onLoad="disable_all_btn(false);get_by_id('user_name').focus();get_login_info('no_auth','fw_ver', 'hw_ver')"-->
	<body onLoad="get_system_info();change_fontsize()">
		<div id="wrapper">
			<!-------------------- Logo ------------------------->
			<div id="Lheader">
				<div id="logo">
						<img id="D-Link logo" src="webfile_images/logo_2.gif" width="105" height="95"  />
				</div>
				<table class="versionTable" id="versionTable" border="0" cellspacing="0">
					<tbody>
						<thead>
					     <td><script>I18N("h", "Model Name");</script> : <? echo query("/runtime/device/modelname"); ?></td>
					     <td><script>I18N("h", "Hardware Version");</script> : <? echo query("/runtime/device/hardwareversion"); ?></td>
					     <td><script>I18N("h", "Firmware Version");</script> : <? echo query("/runtime/device/firmwareversion"); ?></td>
					     <td align="right"><script>I18N("h", "Language");</script>:</td>
						 <td>
				     		<div class="styled-select">
								<select name="Language" id="Language" onChange="switch_Language(this.value)">
									<option value="0">English</option>
									<option value="1">繁體中文</option>
									<option value="2">简体中文</option>
									<option value="3">한국</option>
									<option value="4">français</option>
									<option value="5">português</option>
									<option value="6">Español</option>
									<option value="7">Italiano</option>
									<option value="8">Deutsch</option>
									<option value="9">русский</option>
								</select>
							</div>
						 </td>
						</thead>
					</tbody>
				</table>
			</div>
			<!-------------------- Content ---------------------->
			<div id="content">
				<center><p id="title">SharePort Web Access</p></center>
				<div id="logIn">
					<table class="logInTable" id="logInTable" border="0" cellspacing="0">
						<tbody>
						<thead>
						     <tr>
						     	 <th><span id="Login_Username"><script>I18N("h", "Username");</script></span> : </th>
						     	 <td><input class="styled-text" type="text" name="user_name" size="20" id="user_name"></td>
						     </tr>
						     <tr>
						     	 <th><span id="Login_Password"><script>I18N("h", "Password");</script></span> : </th>
						     	 <td><input class="styled-text" type="password" name="user_pwd" size="20" id="user_pwd"></td>
						     </tr>
						</thead>
						</tbody>
					</table>
					<center><button type="button" id="logIn_btn" class="styled_button_s" onClick="get_auth_info()"><script>I18N("h", "Log In");</script></button></center>
				</div>
			</div>
			<br />
			<div id="link" style="display:none"><script>I18N("h", "To access device management,");</script>&nbsp;<a href="http://<? echo query("/device/hostname");?>.local/"><script>I18N("h", "click here.");</script></a></div>
			<br /><br />
			<div id="footer"><script>I18N("h", "COPYRIGHT");</script></div>
		</div>
	</body>
</html>
