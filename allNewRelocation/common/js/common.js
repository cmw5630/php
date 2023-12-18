var replaceHTML = '<div class="input-group input-group-sm" style="margin-bottom:1px;"><div class="input-group-prepend"><span class="input-group-text"\>기존</span></div><input type="text" class="form-control" name="rep_{repName}_before[]" /><div class="input-group-prepend"><span class="input-group-text">></span></div><input type="text" class="form-control" name="rep_{repName}_after[]"/><div class="input-group-append"><span class="input-group-text">변경</span></div></div>';
var editorPathHTML = '<div class="input-group input-group-sm" style="width:600px;margin-top:3px;"><div class="input-group-prepend"><span class="input-group-text">도메인</span></div><input type="text" style="width:200px;" class="form-control" placeholder="exam.co.kr" name="editorFileDomain[]" /><div class="input-group-prepend"><span class="input-group-text">기본 경로</span></div><input type="text" style="width:200px;" class="form-control" value="/" name="editorFileDefaultPath[]" /></div>';
var separateHTML = '<tr><td align="center"><input type="checkbox" name = "board_code_check[]" /></td><td align="center"><input type="text" class="form-control form-control-sm change" name="before_board[]" /></td><td align="center"><input type="text" class="form-control form-control-sm change" name="boardname[]" /></td><td align="center"><input type="text" class="form-control form-control-sm change" name="after_board[]" /></td><td align="center"><select class="form-control form-control-sm" name="boardKindQa[]" style="width:100px;"><option value="" selected>미사용</option><option value="Y">사용</option></select></td></tr>';

function deliveryCommissionTypeSet() {
	if ($('select[name="deliveryCommission_type"]').find('option:selected').val() == 'sync') {
		$('#deliveryCommissionCnt').val(1);
		fieldCntSet($('#deliveryCommissionCnt'));
		$('#deliveryCommissionCnt').attr('disabled', true);
		$('#deliveryCommission').attr('disabled', true);
		$('#deliveryCommission').val('');
	}
	else {
		$('#deliveryCommissionCnt').attr('disabled', false);
		$('#deliveryCommission').attr('disabled', false);
	}
}

function scmAddressSet(obj) {
	var objName = '';
	if (Left(obj.attr('name'), 9)  == 'unstoring') {
		objName = 'unstoring';
	}
	else {
		objName = 'return';
	}
	
	if (obj.find('option:selected').val() == 'sync') {
		$('input[name="' + objName + 'ZipcodeCnt"]').val(1).attr('disabled', true);
		$('input[name="' + objName + 'AddressCnt"]').val(1).attr('disabled', true);
		fieldCntSet($('input[name="' + objName + 'ZipcodeCnt"]'));
		fieldCntSet($('input[name="' + objName + 'AddressCnt"]'));
		$('input[name="' + objName + 'Zipcode[]"]').attr('disabled', true);
		$('input[name="' + objName + 'Zonecode"]').attr('disabled', true);
		$('input[name="' + objName + 'Address[]"]').attr('disabled', true);
	}
	else {
		$('input[name="' + objName + 'ZipcodeCnt"]').attr('disabled', false);
		$('input[name="' + objName + 'Zipcode[]"]').attr('disabled', false);
		$('input[name="' + objName + 'Zonecode"]').attr('disabled', false);
		$('input[name="' + objName + 'AddressCnt"]').attr('disabled', false);
		$('input[name="' + objName + 'Address[]"]').attr('disabled', false);
	}
}

function passwordTypeSet () {
	if ($('select[name="password_type"]').find('option:selected').val() != 'temp') {
		$('#memPwCnt').val(1);
		fieldCntSet($('#memPwCnt'));
		$('#memPwCnt').attr('disabled', true);
	}
	else {
		$('#memPwCnt').attr('disabled', false);
	}
}

function formAct(id) {
	frm = document.frmMain;
	//frm.boardKindQa[](1).value = 'Y';
	frm.target = id;
	parent.configSubmit(frm);
}

function boardCodeSet(count) {
	objCountCheck($('#tableSetArea tbody'), $('#tableSetArea tbody tr'), count, separateHTML);
}

function fileCopyConfSet(obj) {
	var copyFl = false;
	$('.copyFl').each(function(){
		if ($(this).find('option:selected').val() == 'Y') {
			copyFl = true;
		}
	});

	targetDisplayToggle('copyConf', copyFl);

	typeCopyFl = false;
	if (obj.find('option:selected').val() == 'Y') {
		typeCopyFl = true;
	}

	targetDisplayToggle(obj.attr('dispTarget'), typeCopyFl);
}

function displayFlSet(obj) {
	typeCopyFl = false;
	if (obj.attr('showFlag') == obj.find('option:selected').val()) {
		typeCopyFl = true;
	}
	
	if (obj.attr('dispTarget')) {
		var arrayTarget = obj.attr('dispTarget').split(' ');
		for (i = 1; i <= arrayTarget.length; i++ ){
			targetDisplayToggle(arrayTarget[i - 1], typeCopyFl);
		}
	}
}

function pathFieldFl () {
	if ($('#fieldDataFl').find('option:selected').val() == 'Y') {
		$('#fileAddPath input').attr('placeholder', '필드명 입력');
	}
	else {
		$('#fileAddPath input').attr('placeholder', '추가 경로 입력');
	}
}
			
function replaceCntChangeSet(obj) {
	count = obj.val();
	insertHTML = replaceHTML.replace(/{repName}/g, obj.attr('repName'));
	objCountCheck(obj.parent().parent('td').find('.replaceArea'), obj.parent().parent('td').find('.replaceArea').find('.input-group'), count, insertHTML);
}

//--------------------------------------------------------------
//- Advice - 문자열 오른쪽에서 시작 자르기
//- Right('자를 문자열','자를 문자 갯수');
//--------------------------------------------------------------

var Right = function (str, n){ //메게변수로 문자열 값과 바꿀 n번째문자열
	if (n <= 0) // 만약 n 이 0보다 작거나 같다면 Right 함수에서 0보다 작은경우는 없기에
		return "";
	else if (n > String(str).length) //n이 바꿀 문자열보다 짧다면 
		return str;
	else { //정상적이라면
		var iLen = String(str).length; 
	return String(str).substring(iLen, iLen -  n); // 문자열 끝에서 문자열의 길이 - n까지
	}
}

//--------------------------------------------------------------

//--------------------------------------------------------------
//- Advice - 문자열 왼쪽에서 시작 자르기
//- Left('자를 문자열','자를 문자 갯수');
//--------------------------------------------------------------

var Left = function (str, n){//메게변수로 문자열 값과 바꿀 n번째문자열
	if (n <= 0) // 만약 n 이 0보다 작거나 같다면 Left함수에서 0보다 작은경우는 없기에
		return "";
	else if (n > String(str).length) //n이 바꿀 문자열보다 짧다면 
		return str; // str을 그냥 리턴
	else // 정상적이라면 
		return String(str).substring(0,n); // 0번째 문자열 부터 n번째 문자열을 substring한다
}

var targetDisplayToggle = function (targetID, displayFl) {
	if (displayFl) {
		$('#' + targetID).show();
	}
	else {
		$('#' + targetID).hide();
	}
}

function objCountCheck(targetObj, addObj, count, addHtml) {
	addObjCnt = addObj.length;

	if (count > addObjCnt) {
		for ( i = addObjCnt; i < count; i++) {
			targetObj.append(addHtml);
		}
	}
	else {
		for ( i = addObjCnt; i >= count; i--) {
			addObj.eq(i).remove();
		}
	}
}

function fieldCntSet(obj) {
	fieldName = obj.attr('name').replace('Cnt','');
	objCountCheck($('#' + fieldName).parent('td'), $('#' + fieldName).parent('td').children('.add'), obj.val() - 1, '<input type="text" name="' + fieldName + '[]" class="form-control form-control-sm add float-left change" />');
}

//--------------------------------------------------------------
//- Advice - 숫자 전용 textbox 문구 출력시간동안 입력 불가 후 재입력 동작 함수
//--------------------------------------------------------------
var clearFlag = 0;
var timeClearInterval = '';
function timeClear() {
	clearFlag = 1;
	timeClearInterval = setInterval(function() {
		$('#numberLimit').hide();
		clearFlag = 0;
		clearInterval(timeClearInterval);
	}, 1000);
}


var confSave = function() {
	var json	= new Object();
	var number	= new Object();
	var select	= new Object();
	var text	= new Object();
	var checkbox = new Object();

	var textSaveProc = function() {
		var type = $(this).attr('type');
		if (Right($(this).attr('name'), 2) == '[]') {
			changeNumber = $('input[name="' + $(this).attr('name') + '"]').index($(this));

			eval(type + '[changeNumber + "." + $(this).attr("name")] = $(this).val().replace(/\"/g, \'\\"\')');
		}
		else {
			eval(type + '[$(this).attr("name")] = $(this).val().replace(/\"/g, \'\\"\')');
		}
	}
	

	$('input[type="number"]').each(textSaveProc);

	$('select').each(function() {
		if (Right($(this).attr('name'), 2) == '[]') {
			changeNumber = $('select[name="' + $(this).attr('name') + '"]').index($(this));
			
			select[changeNumber + "." + $(this).attr("name")] = $(this).find('option:selected').val();
		}
		else {
			select[$(this).attr('name')] = $(this).find('option:selected').val();
		}
	});

	$('input[type="text"]').each(textSaveProc);
	
	$('input[type="checkbox"]').each(function() {
		
		if (Right($(this).attr('name'), 2) == '[]') {
			if ($(this).attr('name') != 'board_code_check[]') {
				changeNumber = $('input[name="' + $(this).attr('name') + '"]').index($(this));
				checkbox[changeNumber + "." + $(this).attr("name")] = $(this).prop('checked');
			}
		}
		else {
			checkbox[$(this).attr("name")] = $(this).prop('checked');
		}
		
	})
	
	json['number']	= number;
	json['select']	= select;
	
	json['text']	= text;
	if ($('input[type="checkbox"]').length) {
		json['check']	= checkbox;
	}
	
	jsonText = JSON.stringify(json);

	return json;
}

var confLoad = function(json, maskCloseFl) {
	$.each(json, function(type, list) {
		if (type == 'select') {
			var loadProcess = function(target, value) {
				if (Right(target, 2) == '[]') {
					arrayKey = target.split('.');
					$('select[name="'+arrayKey[1]+'"]:eq('+arrayKey[0]+') option').each(function(){
						if($(this).val() == value){
							$(this).attr('selected','selected');
						}
					});
				}
				else {
					$('select[name="' + target + '"] option').each(function(){
						if($(this).val() == value){
							$(this).attr('selected','selected');
						}
					});
				}
				if (Right(target, 9) == 'data_type') {
					dataTypeSelect($('select[name="' + target + '"]'));
				}
				else if (Right(target, 12) == 'file_copy_yn') {
					fileCopyConfSet($('select[name="' + target + '"]'));
				}
				else if (target == 'table_separate' || target == 'reply_use' || target == 'reply_table_separate' || target == 'reply_table_separate' || target == 'file_use' || target == 'addressTableFl') {
					displayFlSet($('select[name="' + target + '"]'));
				}
				else if (target == 'password_type') {
					displayFlSet($('select[name="' + target + '"]'));
					passwordTypeSet();
				}
				else if (target == 'deliveryCommission_type') {
					deliveryCommissionTypeSet();
				}
				else if (target == 'answer_separate') {
					fileCopyConfSet($('select[name="' + target + '"]'));
					replyDepthAreaDisavledFl();
				}
				else if (target == 'unstoringAddress_type' || target == 'returnAddress_type') {
					scmAddressSet($('select[name="' + target + '"]'))
				}
				else if (target == 'fieldDataFl' || target == 'filePathFieldYn') {
					pathFieldFl();
				}
			}
			loadDataRoop(list, loadProcess);
		}
		else if (type == 'check') {
			var loadProcess = function(target, value) {
				if (Right(target, 2) == '[]') {
					arrayKey = target.split('.');
					$('input[name="'+arrayKey[1]+'"]:eq('+arrayKey[0]+')').prop('checked', value);
				} else {
					$('input[name="' + target + '"]').prop('checked', value);
				}
			};

			loadDataRoop(list, loadProcess);
		}
		else {
			var loadProcess = function(target, value) {
				if (Right(target, 3) == 'Cnt') {
					$('input[name="' + target + '"]').val(value);
					if (Left(target, 3) == 'rep') {
						if ($('input[name="' + target + '"]').hasClass("replaceCount")) {
							replaceCntChangeSet($('input[name="' + target + '"]'));
						}
						else {
							fieldCntSet($('input[name="' + target + '"]'));
						}
					}
					else if (target == 'editorFileDomainCnt') {
						objCountCheck($('#editorPathArea'), $('#editorPathArea').find('.input-group'), value, editorPathHTML);
					}
					else if (target == 'boardCodeCnt') {
						boardCodeSet(value);
					}
					else {
						fieldCntSet($('input[name="' + target + '"]'));
					}
				}
				else if (Right(target, 2) == '[]') {
					arrayKey = target.split('.');
					$('input[name="'+arrayKey[1]+'"]:eq('+arrayKey[0]+')').val(value);
				}
				else {
					$('input[name="' + target + '"]').val(value);
				}
			}
			loadDataRoop(list, loadProcess);
		}
	});

	//alert(maskCloseFl);
	if (maskCloseFl) {
		
		parent.loadMaskClose();
	}
}

var loadDataRoop = function (list, process) {
	$.each(list, function(target, value) {
		process(target, value);
	});
}

var dataTypeSelect = function (obj) {
	if (obj.find('option:selected').val() == 'csv') {
		obj.parent().parent('.dataInfoArea').find('.sql').hide();
		obj.parent().parent('.dataInfoArea').find('.csv').show();
	}
	else {
		obj.parent().parent('.dataInfoArea').find('.csv').hide();
		obj.parent().parent('.dataInfoArea').find('.sql').show();
	}
}

$(document).ready(function() {
	$('.tableArea table tr').on({'mouseover': function() {
		$(this).find('td,th').addClass('config_area_on');
	}, 'mouseout' : function() {
		$(this).find('td,th').removeClass('config_area_on');
	}
	});

	$('.displayFl').on('change', function(){
		displayFlSet($(this));
	});

	$('.replaceCount').on('keyup', function(){
		replaceCntChangeSet($(this));
	}).change(function(){
		replaceCntChangeSet($(this));
	});

	$('#tableTab .nav-link').on('click', function(){
		$('#tableTab .nav-link').removeClass('active');
		$('.tableArea').hide();
		$('#tableTab .nav-link[tabtarget="' + $(this).attr('tabtarget') + '"]').addClass('active');
		$('#' + $(this).attr('tabtarget')).show();
	});

	$('.dataInfoArea div .data_type').on('change', function() {
		dataTypeSelect($(this));
	});

	$('input[onlyNumber]').on(
		{'mouseover' : function() {
			$('.alert').hide();
			if ($('#dialog').length == 0) {
				$('body').append('<div id="dialog" style="position:absolute;" class="alert alert-warning" role="alert"></div>');
			}
			obj = $(this);

			var limitNumber = new Array();
			if (obj.attr('onlyNumber').match(',')) {
				limitNumber = obj.attr('onlyNumber').split(',');
				if (!limitNumber[0]) {
					limitNumber[0] = 0;
				}
			}
			else {
				limitNumber = {0:0,1:obj.attr('onlyNumber')};
			}

			$maxNumber = (limitNumber[1]) ? limitNumber[1] : '무제한';
			$('#dialog').html('숫자 입력 전용 영역 입니다.<br/>가능 숫자 : ' + limitNumber[0] + ' ~ ' + $maxNumber);
			$('#dialog').show();
			commentLayer = $('#dialog');
			commentLayer.css('top',parseInt(obj.offset().top)).css({'left':(parseInt(obj.offset().left)+parseInt(obj.css('width')) + 2), 'z-index':'10'});
		},
		'mouseout' : function() {
			$('#dialog').hide();
		},
		'keydown' : function(){ // 숫자 전용 textbox 처리
			if (!clearFlag) {
				var obj = $(this);
				thisValue = obj.val();
				
				for (var i = 0; i < thisValue.length ; i++){
					chr = thisValue.substr(i,1);  
					chr = escape(chr);
					key_eg = chr.charAt(1);
					if (key_eg == 'u'){
						key_num = chr.substr(i,(chr.length-1));
						if((key_num < "AC00") || (key_num > "D7A3")) { 
							event.returnValue = false;
						}
					}
				}

				var limitNumber = new Array();
				if (obj.attr('onlyNumber').match(',')) {
					limitNumber = obj.attr('onlyNumber').split(',');
					if (!limitNumber[0]) {
						limitNumber[0] = 0;
					}
				}
				else {
					limitNumber = {0:0,1:obj.attr('onlyNumber')};
				}
				
				/*
				var limitNumber = obj.attr('onlyNumber').split(',');
						alert(limitNumber);
				*/
				
				if ((event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode == 8 || event.keyCode ==46) || (event.keyCode >= 96 && event.keyCode <= 105) || (event.keyCode >= 37 && event.keyCode <= 40)) {
					event.returnValue = true;
					var numberCheck = setInterval(function(){

						var msgFl = false;

						if ($('#numberLimit').length == 0) {
							$('body').append('<div id="numberLimit" style="position:absolute;display:none;" class="alert alert-danger" role="alert"></div>');
						}

						if (limitNumber[1]) {
							if (Number(limitNumber[1]) < Number(obj.val())) {
								msgFl = true;
								$('#numberLimit').html('변경하신 수치는 최대 ' + limitNumber[1] + ' 까지 설정이 가능합니다.');
								timeClear();
								obj.val(limitNumber[1]);
							}
						}

						if (Number(limitNumber[0]) > Number(obj.val())) {
							
							msgFl = true;
							$('#numberLimit').html('변경하신 수치는 ' + limitNumber[0] + ' 보다 작을 수 없습니다.');
							timeClear();
							obj.val(limitNumber[0]);
						}

						if (msgFl) {
							$('.alert').hide();
							$('#numberLimit').css('top',parseInt(obj.offset().top)).css({'left':(parseInt(obj.offset().left)+parseInt(obj.css('width')) + 2), 'z-index':'10'}).show();
							$('#numberLimit').show();
						}
						clearInterval(numberCheck);
					}, 1);
				} else {
					if(event.preventDefault){
						event.preventDefault();
					} else {
						event.returnValue = false;
					}
				}

			}
			else {
				if(event.preventDefault){
					event.preventDefault();
				} else {
					event.returnValue = false;
				}
			}
		}
	
	}).change(function(){
		if (!clearFlag) {
			var obj = $(this);
			thisValue = obj.val();

			var limitNumber = new Array();
			if (obj.attr('onlyNumber').match(',')) {
				limitNumber = obj.attr('onlyNumber').split(',');
				if (!limitNumber[0]) {
					limitNumber[0] = 0;
				}
			}
			else {
				limitNumber = {0:0,1:obj.attr('onlyNumber')};
			}

			var numberCheck = setInterval(function(){

				var msgFl = false;

				if ($('#numberLimit').length == 0) {
					$('body').append('<div id="numberLimit" style="position:absolute;display:none;" class="alert alert-danger" role="alert"></div>');
				}

				if (limitNumber[1]) {
					if (Number(limitNumber[1]) < Number(obj.val())) {
						msgFl = true;
						$('#numberLimit').html('변경하신 수치는 최대 ' + limitNumber[1] + ' 까지 설정이 가능합니다.');
						timeClear();
						obj.val(limitNumber[1]);
					}
				}

				if (Number(limitNumber[0]) > Number(obj.val())) {
					
					msgFl = true;
					$('#numberLimit').html('변경하신 수치는 ' + limitNumber[0] + ' 보다 작을 수 없습니다.');
					timeClear();
					obj.val(limitNumber[0]);
				}

				if (msgFl) {
					$('.alert').hide();
					$('#numberLimit').css('top',parseInt(obj.offset().top)).css({'left':(parseInt(obj.offset().left)+parseInt(obj.css('width')) + 2), 'z-index':'10'}).show();
					$('#numberLimit').show();
				}
				clearInterval(numberCheck);
			}, 1);
		}
	});

	$('.fieldCnt').on('keydown',function(){
		var obj = $(this);
		var fieldCheck = setInterval(function(){
			fieldCntSet(obj);
			clearInterval(fieldCheck);
		}, 10);
	}).change(function() {
		var obj = $(this);
		var fieldCheck = setInterval(function(){
			fieldCntSet(obj);
			clearInterval(fieldCheck);
		}, 10);
	});

	$('input[name="editorFileDomainCnt"]').on({
		'keyup': function() {
			objCountCheck($('#editorPathArea'), $('#editorPathArea').find('.input-group'), $(this).val(), editorPathHTML);
		}
	});
});