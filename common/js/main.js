var confTab = new Tab();
var addTabNumber = 1;
var save_url = 'http://relocation.godo.co.kr/module/newsave.php';
var setDomain = '';
var tabCopyFlag = false;
var copyTabId = '';
var zipFlag	= '';
var saveSolutionList = new Array;
var saveSolutionFl = true;

var addTabAreaClose = function () {
	$('#addTabInput').hide();
	$('#noneBackground').hide();
}

var zipAreaClose = function () {
	$('#addPasswordInput').hide();
	//$('#noneBackground').hide();
}

var alertLayer = function (msg) {
	$('#noneBackground').css('top', $(document).scrollTop()).height($(window).height()).show();
	$('#alertArea #msgPrint').html(msg);
	$('#alertArea').css({'left':($(document).width() / 2) - ($('#alertArea').width() / 2), 'top':($(document).height() / 2) - ($('#alertArea').height() / 2) - 100}).slideDown(400);
	$('#alertClose').focus();
}

function dbInfoAreaSet() {
	$.ajax({
		dataType:"xml",type : 'POST',url : './config/defaultDBInfo.xml'
		,data : ''//넘길 파라메타 값
		,success: function(xml) {
			$('input[name="connect_host"]').val($(xml).find('host').text());
			$('input[name="connect_name"]').val($(xml).find('name').text());
			$('input[name="connect_id"]').val($(xml).find('id').text());
			$('input[name="connect_pwd"]').val($(xml).find('pwd').text());
		}
		,error: function() {
			alertLeyer('DB Info Set 페이지가 정상 로드 되지 않았습니다.');
		}
	});
}

var saveFileLoad = function (){						//선택 되어 있는 설정저장파일 로드
	// 저장되어 있는 json 객체를 불러 와 파싱 하여 각 설정값 변경 시작 ---------
	$('#load_file').html('');
	
	param = "mode=read_list&save_mode=allData&file_dir="+setDomain;
	var sucFunc = function(data){
		if(data.result == 'true'){
			$.each(data,function(key,value){
				if(Left(key,8) == 'savefile'){
					$('#load_file').append('<option value="'+value+'">'+value+'</option>');
				}
			});
		}
	}
	var errorFunc = function(){
		alertLayer('저장 된 설정을 찾을 수 없습니다.');
		$('#load_file').append('<option>-- 로드 파일 없음 --</option>');
	}
	
	ajaxProcess(save_url, param, sucFunc, errorFunc);
}

var save = function (jsonText, fileName, fileDir) {
	if (!fileDir) {
		alertLayer('대상도메인 설정 후 저장 해주시기 바랍니다.');
	}
	else {
		$('input[name="file_dir"]').val(fileDir);
		$('input[name="file_name"]').val(fileName);
		$('input[name="json_data"]').val(jsonText);
		document.frmSave.submit();
	}
}

if (window.addEventListener) {
	window.addEventListener('message', postMessageController, true);
}else if(window.attachEvent) {
	window.attachEvent('onmessage', postMessageController);
}

function postMessageController(_thisEvent){
	if(_thisEvent.origin !== 'http://relocation.godo.co.kr') {
		alert('크로스 도메인 보안 에러');
	}
	else {
		if (_thisEvent.data.result) {
			alertLayer('Save Success : ' + _thisEvent.data.saveFileName);
			if (_thisEvent.data.fileDir != 'allDataSolution') {
				saveFileLoad();
			}
		}
		else {
			alertLayer('Save Error');
		}
		
	}
}

//--------------------------------------------------------------
//- Advice - 선택 되어 있는 설정저장파일 로드
//- form_load('솔루션여부') type 값이 있으면 무조건 솔루션 로드
//--------------------------------------------------------------

var load = function (type){						//선택 되어 있는 설정저장파일 로드
	$('#noneBackground').css('top', $(document).scrollTop()).height($(window).height()).show();
	$('#noneBackgroundLoading').css({'left':($(document).width() / 2) - ($('#noneBackgroundLoading').width() / 2), 'top':($(document).height() / 2) - ($('#noneBackgroundLoading').height() / 2) - 100}).show();
	if(type){
		var dir = "allDataSolution";
		var file = $('select[name="solutionFileName"]').find('option:selected').val() + '.crs';
	} else {
		var dir = setDomain;
		var file = $('select[name="load_file"]').find('option:selected').val();
	}

	param = "mode=load&save_mode=allData&file_name="+file+"&file_dir="+dir;
	var sucFunc = function(data){
		if(data.result) {
			var maskCloseFl = false;
			var loadCount = Object.keys(data.json_data).length;
			var roopCount = 1;

			$.each(data.json_data, function(key, value) {
				if (key == 'tabmanu') {
					$('.nav-link').each(function() {
						confTab.Del($(this));
					});
					
					$.each(value, function(manuid, manuname) {
						confTab.Add(manuid, manuname.tabType, unescape(manuname.tabName));
						pattern = /^addTab/;
						if (pattern.test(manuid)) {
							addTabNumber = Number(manuid.replace('addTab', '')) + 1;
							//alert(Number(manuid.replace('addTab', '')) + ' : ' + addTabNumber);
						}

					});
				}
				else {
					//alert(loadCount + ' : ' + roopCount);
					if (loadCount == roopCount) {
						maskCloseFl = true;
					}
					var tableConfLoad = setInterval(function() {
						eval('document.getElementById("' + key + '").contentWindow.confLoad(value,maskCloseFl)');
						clearInterval(tableConfLoad);
					}, 1000);
				}

				roopCount++;
			});
		}
	}
	var errorFunc = function(){
		alertLayer('Load Error');
	}

	ajaxProcess(save_url, param, sucFunc, errorFunc);
}

function loadMaskClose() {
	$('#noneBackgroundLoading').hide();
	$('#noneBackground').hide();
}


var ajaxProcess = function (targetUrl, param, sucFunc, errorFunc) {
	//생성된 json 객체를 설정되어 있는 서버에 저장 -----------------------------
	$.ajax({
		dataType:"jsonp",jsonp:'callback',type : 'POST',url : targetUrl
		,data : param//넘길 파라메타 값
		,success: sucFunc
		,error: errorFunc
	});
}

function saveSolutionLoad() {
	var sucFunc = function (data){
		$.each(data, function(key, value) {
			$('select[name="solutionFileName"]').append('<option value="' + key + '">' + value + '</option>');
			saveSolutionList.push(key);
		});
	};
	var errorFunc = function() {
		alertLayer('솔루션 로드에 실패 했습니다.');
	}

	ajaxProcess('http://relocation.godo.co.kr/module/_save/solution.php', 'mode=load', sucFunc, errorFunc);
}

function versionCheck() {
	var sucFunc = function(data) {
		if (data.result) {
			$('#updateAlert').slideDown();
		}
	}
	var errorFunc = function() {
		alert('version check error');
	}
	ajaxProcess('./lib/versionCheck.php', '',sucFunc , errorFunc);
}

function urlencode(str) {
	str = (str + '').toString();
	return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
}


function urldecode(str) {
	return decodeURIComponent((str + '').replace(/%(?![\da-f]{2})/gi, function() {
		return '%25';
	}).replace(/\+/g, '%20'));
}

var patchLoad = function () {

	param = "";
	targetUrl = 'http://relocation.godo.co.kr/module/_source/defaultPatchFileCheck.php';
	var sucFunc = function(data) {
		$('#verSelect').html('');
		if (data.result) {
			$.each(data.fileList, function(oriName, koreanName) {
				$('#verSelect').append('<option value="' + urldecode(oriName) + '">' + urldecode(koreanName) + '</option>');
			});
		}
		else {
			$('#verSelect').html('<option value="">-- 설치 가능 버전 없음 --</option>');
		}
	}
	var errorFunc = function(){

		alertLayer('Patch Lists Error;');
	}
	ajaxProcess(targetUrl, param, sucFunc, errorFunc);
}

var serverPatchLoad = function () {

	param = "setDomain=" + setDomain;
	targetUrl = 'http://relocation.godo.co.kr/module/_source/patchFileCheck.php';
	var sucFunc = function(data) {
		$('#verServerSelect').html('');
		if (data.result) {
			$.each(data.fileList, function(oriName, koreanName) {
				$('#verServerSelect').append('<option value="' + urldecode(oriName) + '">' + urldecode(koreanName) + '</option>');
			});
		}
		else {
			$('#verServerSelect').html('<option value="">-- 설치 가능 버전 없음 --</option>');
		}
	}
	var errorFunc = function(){
		alertLayer('Patch Lists Error;');
		$('#verServerSelect').html('<option value="">-- 설치 가능 버전 없음 --</option>');
	}
	ajaxProcess(targetUrl, param, sucFunc, errorFunc);
}

//--------------------------------------------------------------
//- Advice - 문자열 왼쪽에서 시작 자르기
//- Left('자를 문자열','자를 문자 갯수');
//--------------------------------------------------------------

function Left(str, n){//메게변수로 문자열 값과 바꿀 n번째문자열
	if (n <= 0) // 만약 n 이 0보다 작거나 같다면 Left함수에서 0보다 작은경우는 없기에
		return "";
	else if (n > String(str).length) //n이 바꿀 문자열보다 짧다면 
		return str; // str을 그냥 리턴
	else // 정상적이라면 
		return String(str).substring(0,n); // 0번째 문자열 부터 n번째 문자열을 substring한다
}

var saveDataSet = function () {
	var json = new Object();
	json['tabmanu'] = confTab.Save();

	$('#manuBox iframe').each(function(){
		if ($(this).attr('tabtype')) {
			eval('if (document.getElementById("' + $(this).attr('id') + '").contentWindow.confSave) { json["' + $(this).attr('id') + '"] = document.getElementById("' + $(this).attr('id') + '").contentWindow.confSave();}');
		}
	});

	return json;
}

var dbInfoLayerOpen = function() {
	$('#noneBackground').css('top', $(document).scrollTop()).height($(window).height()).show();
	$('#connectSet').css({'left':($(document).width() / 2) - ($('#connectSet').width() / 2), 'top':($(document).height() / 2) - ($('#connectSet').height() / 2) - 100}).slideDown(600);
	$('input[name="connect_host"]').focus();
}

var solutionInfoAddInit = function() {
	$('#saveSolutionAdd').hide();
	$('#noneBackground').hide();
	$('#solutionCode').val('');
	$('#solutionName').val('');
	$('#dialog').hide();
}

var dataListCheck = function(layerViweFl) {
	targetUrl = './common/php/dataFileListCheck.php';
	param = '';

	var sucFunc = function (data) {
		targetObj = $('#dataFileSetArea');
		targetObj.find('table tbody').html('');

		if (data.length) {
			var listCount = 1;
			
			if (!$('input[name="allPathCheck"]').is(':checked')) {
				$('input[name="allPathCheck"]').prop('checked', true);
			}

			$.each(data, function(key, path) {
				var addHtml = '<tr><td><input type="checkbox" name="dataFileListCheck[]" id="dataFileList' + listCount + '" checked value="' + path + '" /></td><td><label for="dataFileList' + listCount + '" style="cursor:pointer;">' + path + '</label></td></tr>';
				targetObj.find('table tbody').append(addHtml);

				listCount++;
			});

			$('input[name="dataFileListCheck[]"]').off();
			$('input[name="dataFileListCheck[]"]').on('change', function() {
				if (!$(this).is(':checked') && $('input[name="allPathCheck"]').is(':checked')) {
					$('input[name="allPathCheck"]').prop('checked', false);
				}
				
				if ($(this).is(':checked') && !$('input[name="allPathCheck"]').is(':checked')) {
					var allCheckFl = true;
					$('input[name="dataFileListCheck[]"]').each(function() {
						if (!$(this).is(':checked')) {
							allCheckFl = false;
						}
					});
					if (allCheckFl) {
						$('input[name="allPathCheck"]').prop('checked', true);
					}
				}
			})
		}
		else {
			var addHtml = '<tr><td colspan="2">데이터 파일이 존재 하지 않습니다.</td></tr>';
			targetObj.find('table tbody').append(addHtml);
		}

		if (layerViweFl) {
			targetObj.show("slide");
		}
	}

	var errorFunc = function(){
		alertLayer('데이터 파일 검색에 실패 했습니다.');
	}

	ajaxProcess(targetUrl, param, sucFunc, errorFunc);

}


$(document).ready(function(){
	$('#dataListPathclose').on('click', function(){
		$('#dataFileSetArea').hide("slide");
	}).css('cursor', 'pointer');

	$('#solutionInfoAdd').on('click', function(){
		if(!saveSolutionFl) {
			alert('코드명을 다시 확인 해 주세요');
			$('#solutionCode').focus();

			return false;
		}

		if ($('#solutionName').val() == '') {
			alert('솔루션명 입력이 되어 있지 않습니다.');
			$('#solutionName').focus();
			return false;
		}

		var sucFunc = function (data){
			if (data.result) {
				$('select[name="solutionFileName"]').append('<option value="' + $('#solutionCode').val() + '">' + $('#solutionName').val() + '</option>');
				saveSolutionList.push($('#solutionCode').val());
				solutionInfoAddInit();
				alertLayer('솔루션 저장 항목에 정상적으로 추가 했습니다.');
			}
			else {
				solutionInfoAddInit();
				alertLayer('솔루션 정보 입력에 실패 했습니다.');
			}
		};
		var errorFunc = function() {
			solutionInfoAddInit();
			alertLayer('솔루션 정보 입력에 실패 했습니다.');
		}
		
		solutionName = urlencode($('#solutionName').val());
		ajaxProcess('http://relocation.godo.co.kr/module/_save/solution.php', 'mode=save&name=' + solutionName + '&code=' + $('#solutionCode').val(), sucFunc, errorFunc);
	});
	
	$('button[name="solutionAdd"]').on('click', function(){
		$('#noneBackground').show();
		$('#saveSolutionAdd').slideDown().css({'left':($(document).width() / 2) - ($('#saveSolutionAdd').width() / 2), 'top' : '10px'});
		$('#solutionCode').focus();
	});
	$('#solutionInfoCancel').on('click', function(){
		solutionInfoAddInit();
	});

	$('#solutionCode').on({'change': function(){
		var flag = false;
		var ttt = $(this);
		var regFl = true;
		
		$.each(saveSolutionList, function(key, value) {
			if (ttt.val() == value) {
				flag = true;
				saveSolutionFl = false;
			};
		});

		var strReg = /^[a-zA-Z]{1}[a-zA-Z0-9]{3,5}$/;
		if (!strReg.test(ttt.val())) {
			flag = true;
			saveSolutionFl = false;
			regFl = false;
		}

		if (flag) {
			if ($('#dialog').length == 0) {
				$('body').append('<div id="dialog" style="position:absolute;" class="alert alert-warning" role="alert"></div>');
			}
			else {
				$('#dialog').hide();
			}
			
			obj = $(this);
			
			if (regFl) {
				$('#dialog').html('사용할 수 없는 코드명 입니다.');
			}
			else {
				$('#dialog').html('코드명 형식 에러 : 영문 시작, 영문 + 숫자 조합 4~6');
			}

			$('#dialog').show();
			commentLayer = $('#dialog');
			commentLayer.css('top',parseInt(obj.offset().top) - 6).css({'left':(parseInt(obj.offset().left)+parseInt(obj.css('width')) + 2), 'z-index':'102'});
			$('#solutionCode').focus();
			//alert();
		}
		else {
			saveSolutionFl = true;
		}
		
	},
	'keydown': function() {
		$('#dialog').hide();
	}});

	var addTabType = '';

	$('#updateSet').on('click', function() {
		$('#updataNotiArea').css({'left':($(document).width() / 2) - ($('#updataNotiArea').width() / 2), 'top':($(document).height() / 2) - ($('#updataNotiArea').height() / 2) - 100}).html('<p id="updateLoading" style="z-index:102;"><img src="./image/icon/updating.gif" /></p>');
		$('#updateProgressArea').show();
		targetUrl = './lib/versionUpdate.php';
		var sucFunc = function(data) {
			if (data.result) {
				$('#updataNotiArea').html('<font style="font-weight:bold;">업데이트 완료</font><br />' + data.versionInfo + '<br /><button class="btn btn-outline-primary" id="updateReload" type="button" onclick="javascript:location.reload();">확인</button>');
			}
			else {
				$('#updateProgressArea').hide();
				if (data.errCode == 1) {
					alertLayer('SFTP 접근 모듈 불러오기에 실패 했습니다.');
				}
				else if (data.errCode == 2) {
					alertLayer('FTP 경로 접속 과정에 문제가 있습니다.');
				}
				else if (data.errCode == 3) {
					alertLayer('다운로드 과정에 문제가 있습니다.');
				}
				else if (data.errCode == 4) {
					alertLayer('압축 해지 과정에 문제가 있습니다.');
				}
			}
		}
		var errorFunc = function(){
			$('#updateProgressArea').hide();
			alert('버전 업데이트에 문제가 있습니다.');
		}
		ajaxProcess(targetUrl, '', sucFunc, errorFunc);
	});

	$('#dbInfoSave').on('click', function() {
		param = "setDomain=" + setDomain + "&connect_host=" + $('input[name="connect_host"]').val() + "&connect_name=" + $('input[name="connect_name"]').val() + "&connect_id=" + $('input[name="connect_id"]').val() + "&connect_pwd=" + $('input[name="connect_pwd"]').val();
		targetUrl = './module/dbInfoSet.php';

		var sucFunc = function(data) {
			if (data.result) {
				$('#connectSet').hide();
				alertLayer('정상 설치 완료 경로 :<br /> ./module/' + setDomain + '/');
			}
			else {
				$('#connectSet').hide();
				alertLayer('DB 정보 셋팅에 에러가 있습니다.');
			}
		}
		var errorFunc = function(){
			$('#connectSet').hide();
			alertLayer('DB 정보 셋팅에 에러가 있습니다.');
		}
		ajaxProcess(targetUrl, param, sucFunc, errorFunc);
	});

	$('#allConfToogle').on('click', function(){
		if ($('#allConf tbody').css('display') == 'none') {
			targetDisplayToggle('allConf tbody', true);
		}
		else {
			targetDisplayToggle('allConf tbody', false);
		}
	});

	$('button[name="shopSave"]').on('click', function(){
		var json = new Object();
		
		json = saveDataSet();

		jsonText = JSON.stringify(json);
		save(jsonText, $('input[name="saveFileName"]').val(), setDomain);
	});

	$('button[name="solutionSave"]').on('click', function(){
		if (confirm('기존 저장된 설정이 있는 경우 덮어쓰기 합니다.\n진행 하시겠습니까?')) {
			var json = new Object();
			
			json = saveDataSet();

			jsonText = JSON.stringify(json);

			save(jsonText, $('select[name="solutionFileName"]').find('option:selected').val(), 'allDataSolution');
		}
	})

	$('button[name="shopLoad"]').on('click', function(){
		load();
	});

	$('button[name="solutionLoad"]').on('click', function(){
		load(true);
	});

	$('#alertClose').on('click', function(){
		$('#alertArea').hide();
		$('#noneBackground').hide();
		$('#noneBackgroundLoading').hide();
	});

	$('#domainSet').on('click', function(){
		setDomain = $('#targetDomain').val();
		saveFileLoad();
		serverPatchLoad();
	});

	$('#tabAdd').on('click', function() {
		var tabId = 'addTab' + addTabNumber;
		if (tabCopyFlag) {
			confTab.Copy(tabId, addTabType, $('#addTabName').val(), copyTabId);
			tabCopyFlag = false;
			copyTabId = '';
			$('#addTabInput').hide();
		}
		else {
			confTab.Add(tabId, addTabType, $('#addTabName').val());
			addTabAreaClose();
		}
		
		addTabNumber++;
	});

	$('#addTabName').on('keydown', function(evt) {
		if (evt.keyCode==13){
			var tabId = 'addTab' + addTabNumber;
			if (tabCopyFlag) {
				confTab.Copy(tabId, addTabType, $('#addTabName').val(), copyTabId);
				tabCopyFlag = false;
				copyTabId = '';
				$('#addTabInput').hide();
			}
			else {
				confTab.Add(tabId, addTabType, $('#addTabName').val());
				addTabAreaClose();
			}
			
			addTabNumber++;
		}

		if (evt.keyCode == 27){
			addTabAreaClose();
		}
	});

	$('.tabAddType').on('click', function(){
		$('#addTabName').val($(this).text());
		addTabType = $(this).attr('tabtype');
		$('#noneBackground').show();
		$('#addTabInput').slideDown().css({'left':($(document).width() / 2) - ($('#addTabInput').width() / 2), 'top' : '10px'});
		 $('#addTabName').focus();
	});

	$('#tabCancel').on('click', function() {
		addTabAreaClose();
	});

	$('#zipArc').on('click', function(){
		zipFlag = 'y';
		$('#zipAdd').text('압축 설정');
		addPasswordSet();
	});

	$('#unzipArc').on('click', function(){
		zipFlag = 'n';
		$('#zipAdd').text('압축 해제');
		addPasswordSet();
	});

	var addPasswordSet = function() {
		if (setDomain) {
			$('#noneBackground').show();
			$('#addPasswordInput').slideDown().css({'left':($(document).width() / 2) - ($('#addPasswordInput').width() / 2), 'top' : '10px'});
			$('#addPasswordName').focus();
		}
		else {
			alertLayer('대상도메인 설정 후 압축 관련 작업이 가능 합니다.');
		}
	}

	$('#zipCancel').on('click', function() {
		zipAreaClose();
		$('#noneBackground').hide();
	});

	$('#addPasswordName').on('keydown', function(evt) {
		if (evt.keyCode==13){
			addPasswordProc();
		}

		if (evt.keyCode == 27){
			zipAreaClose();
			$('#noneBackground').hide();
		}
	});

	$('#zipAdd').on('click', function(){
		addPasswordProc();
	})

	var addPasswordProc = function() {
		$('#noneBackground').css('top', $(document).scrollTop()).height($(window).height()).show();
		$('#noneBackgroundLoading').css({'left':($(document).width() / 2) - ($('#noneBackgroundLoading').width() / 2), 'top':($(document).height() / 2) - ($('#noneBackgroundLoading').height() / 2) - 100}).show();
	
		$.ajax({
			dataType:"json",type : 'POST',url : './common/php/zipPassword.php'
			,data : 'zipFlag=' + zipFlag + '&afterUrl=' + setDomain + '&setPassword=' + $('#addPasswordName').val()//넘길 파라메타 값
			,success: function(data) {
				var zipText = '압축';
				if (zipFlag == 'n') {
					zipText = '해제';
				}

				if (data.result) {
					loadMaskClose();
					alertLayer('정상적으로 ' + zipText + ' 작업이 완료 되었습니다.');
					$('#addPasswordName').val('');
				}
				else {
					loadMaskClose();
					alertLayer(zipText + ' 작업에 실패 하였습니다.');
				}
			}
			,error: function() {
				loadMaskClose();
				alertLayer('압축/해제 작업에 실패 했습니다.');
			}
		});

		zipAreaClose();
	}


	$('.nav-link').on({'click': function() {
			if ($(this).attr('id')) {
				confTab.Select($(this));
			}
		}, 'dblclick' : function() {
			if ($(this).attr('id')) {
				confTab.Del($(this))
			}
		}
	});

	$('#mode').on('click', function(){
		var hideFl = false;
		if ($(this).val() == 'start_q') hideFl = true;
		targetDisplayToggle('queryRoopLimit', hideFl);
	})

	$('#verSetting').on('click', function () {
		selectFile = $('#verSelect').find('option:selected').val();
		if (setDomain) {
			if (selectFile) {
				param = "setDomain=defaultSource&insFileName=" + urlencode(selectFile) + "&beforeDomain=" + setDomain;
				targetUrl = './patch/_fileDLoadInstall.php';

				var sucFunc = function(data) {
					if (data.result) {
						dbInfoLayerOpen();
					}
					else {
						if (data.errCode == 1) {
							alertLayer('SFTP 접근 모듈 불러오기에 실패 했습니다.');
						}
						else if (data.errCode == 2) {
							alertLayer('FTP 경로 접속 과정에 문제가 있습니다.');
						}
						else if (data.errCode == 3) {
							alertLayer('다운로드 과정에 문제가 있습니다.');
						}
						else if (data.errCode == 4) {
							alertLayer('압축 해지 과정에 문제가 있습니다.');
						}
					}
				}
				var errorFunc = function(){
					alertLayer('Patch Server Download Error;');
				}
				ajaxProcess(targetUrl, param, sucFunc, errorFunc);
			}
			else {
				alertLayer('서버 저장 파일을 선택해 주시기 바랍니다.');
			}
		}
		else {
			alertLayer('대상도메인 설정 후 버전을 셋팅 해주시기 바랍니다.');
		}
	});

	$('#verServerSetting').on('click', function() {
		selectFile = $('#verServerSelect').find('option:selected').val();
		if (setDomain) {
			if (selectFile) {
				param = "setDomain=" + setDomain + "&insFileName=" + urlencode(selectFile);
				targetUrl = './patch/_fileDLoadInstall.php';

				var sucFunc = function(data) {
					if (data.result) {
						dbInfoLayerOpen();
					}
					else {
						if (data.errCode == 1) {
							alertLayer('SFTP 접근 모듈 불러오기에 실패 했습니다.');
						}
						else if (data.errCode == 2) {
							alertLayer('FTP 경로 접속 과정에 문제가 있습니다.');
						}
						else if (data.errCode == 3) {
							alertLayer('다운로드 과정에 문제가 있습니다.');
						}
						else if (data.errCode == 4) {
							alertLayer('압축 해지 과정에 문제가 있습니다.');
						}
					}
				}
				var errorFunc = function(){
					alertLayer('Patch Server Download Error;');
				}
				ajaxProcess(targetUrl, param, sucFunc, errorFunc);
			}
			else {
				alertLayer('서버 저장 파일을 선택해 주시기 바랍니다.');
			}
		}
		else {
			alertLayer('대상도메인 설정 후 버전을 셋팅 해주시기 바랍니다.');
		}
	});

	$('input[name="allPathCheck"]').on('change', function(){
		if ($(this).is(':checked')){
			$('input[name="dataFileListCheck[]"]').prop('checked', true);
		}
		else {
			$('input[name="dataFileListCheck[]"]').prop('checked', false);
		}
	});

	$('#dataReset').on('click', function() {
		dataListCheck(true);
	});

	$('button[name="sourceShare"]').on('click', function() {
		if (setDomain) {
			param = "setDomain=" + setDomain;
			targetUrl = './patch/_fileUpload.php';

			var sucFunc = function(data) {
				if (data.result) {
					alertLayer('정상 업로드 완료 경로 :<br /> http://relocation.godo.co.kr/module/_sorce/' + setDomain + '/' + data.fileName);
					serverPatchLoad();
				}
				else {
					if (data.errCode == 1) {
						alertLayer('SFTP 접근 모듈 불러오기에 실패 했습니다.');
					}
					else if (data.errCode == 2) {
						alertLayer('FTP 경로 접속 과정에 문제가 있습니다.');
					}
					else if (data.errCode == 3) {
						alertLayer('FTP 경로 생성 과정에 문제가 있습니다.');
					}
					else if (data.errCode == 4) {	
						alertLayer('업로드 과정에 문제가 있습니다.');
					}
					else if (data.errCode == 5) {
						alertLayer('업로드 될 버전 소스가 없습니다.');
					}
				}
			}
			var errorFunc = function(){
				alertLayer('Patch Install Error;');
			}
			ajaxProcess(targetUrl, param, sucFunc, errorFunc);
		}
		else {
			alertLayer('대상도메인 설정 후 버전을 업로드 해주시기 바랍니다.');
		}
	});

	$('#actTabSubmit').on('click', function(){
		if (!confTab.config.progressFl) {
			if (setDomain) {
				if ($('#manuTab .nav-link').length > 0) {
					$('#manuTab .nav-link').each(function(){
						if ($(this).hasClass('active')) {
							if (Left($(this).attr('id'), 4) != 'act_') {
								eval('document.getElementById("frame_' + $(this).attr('id') + '").contentWindow.formAct("' + $(this).attr('id') + '")');
							}
							else {
								alertLayer('현재 활성화 된 탭은 실행이 불가능 합니다.');
							}
						}
					});
				}
				else {
					alertLayer('현재 열려 있는 탭이 없습니다.');
				}
			}
			else {
				alertLayer('대상도메인 설정 후 실행 해주시기 바랍니다.');
			}
		}
		else {
			alertLayer('현재 실행 중인 실행창이 있습니다. 실행 중인 탭 명 : ' + $('#' + confTab.config.progressId).text());
		}
	});

	$('#tabCopy').on('click', function() {
		$('#manuTab .nav-link').each(function(){
			if ($(this).hasClass('active')) {
				if (Left($(this).attr('id'), 4) != 'act_') {
					$('#addTabName').val($(this).text());
					addTabType = $('#frame_' + $(this).attr('id')).attr('tabtype');
					$('#noneBackground').show();
					$('#addTabInput').slideDown().css({'left':($(document).width() / 2) - ($('#addTabInput').width() / 2), 'top' : '10px'});
					$('#addTabName').focus();
					tabCopyFlag = true;
					copyTabId = $(this).attr('id');
				}
				else {
					alertLayer('현재 활성화 된 탭은 복사가 불가능 합니다.');
				}
			}
		});
	});

	$('#allTabSubmit').on('click', function() {
		alertLayer('준비중인 기능 입니다.');
	});

	$('#csvview').on('click', function(){
		if (!setDomain) {
			alertLayer('대상 도메인 적용 후 실행이 가능 합니다.');
		}
		else {
			window.open('./common/php/csv.php?setDomain=' + setDomain);
		}
	});
});

function configSubmit(frmData) {
	var frmMain = document.mainForm;
	frmData.action = './module/' + setDomain + '/' + frmData.frmActionFileName.value;
	frmData.afterUrl.value			= setDomain;
	frmData.insertMode.value		= frmMain.insertMode.value;
	frmData.mode.value				= frmMain.mode.value;
	frmData.queryRoopLimit.value	= frmMain.queryRoopLimit.value;

	var tabId = 'act_' + frmData.target;
	var tabName = $('#' + tabId.replace('act_', '')).text() + ' 탭 실행 결과';

	confTab.actAdd(tabId, tabName);
	frmData.target = 'frame_' + tabId;

	confTab.config.progressFl = true;
	confTab.config.progressId = tabId;

	frmData.submit();
	var selectActTab = setInterval(function() {
		confTab.Select($('#' + tabId));
		clearInterval(selectActTab);
	}, 500);
}

function configSubmitComplete (dataCount) {
	$('#' + confTab.config.progressId).find('.progLoding').hide();
	alert("'" + $('#' + confTab.config.progressId).text() + "' 총 : " + dataCount + " 건 실행이 완료 됬습니다.");
	confTab.actTabInit();
}

versionCheck();
patchLoad();
dbInfoAreaSet();
saveSolutionLoad();