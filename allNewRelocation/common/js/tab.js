function Tab() {
	this.config = {
		progressFl:false,
		progressId:''
	};
};

Tab.prototype.Add = function (tabId, tabType, tabName) {
	var tabJs = this;

	var manuTab = $('#manuTab');
	var manuBox = $('#manuBox');

	if ((tabType == 'order' || tabType == 'excelArrange') && !setDomain) {
		alertLayer('대상 도메인 적용 후 사용 가능 합니다.');
		//document.all.afterUrl.focus();
		return false;
	}

	manuTab.append('<li class="nav-item"><span class="nav-link" id="' + tabId + '">' + tabName + '</span></li>');
	manuBox.append('<iframe name="frame_' + tabId + '" id="frame_' + tabId + '" frameborder="0" tabtype="' + tabType + '"class="w-100" style="overflow-x:auto;display:none;height:75%;" marginwidth="0" marginheight="0" src="./' + tabType + '.html"></iframe>');

	$('.nav-link').off();
	$('.nav-link').on({'click': function() {
			if ($(this).attr('id')) {
				tabJs.Select($(this));
			}
		}, 'dblclick' : function() {
			if ($(this).attr('id')) {
				tabJs.Del($(this))
			}
		}
	});
	
	this.Select($('#' + tabId));
}

Tab.prototype.Copy = function (tabId, tabType, tabName, oriTabId) {
	if (tabType == 'order' || tabType == 'excelArrange') {
		alertLayer('현재 탭은 복사 처리가 불가능 합니다.');
		return false;
	}

	$('#noneBackgroundLoading').css({'left':($(document).width() / 2) - ($('#noneBackgroundLoading').width() / 2), 'top':($(document).height() / 2) - ($('#noneBackgroundLoading').height() / 2) - 100}).show();
	
	var tabJs = this;

	var json = new Object();
	
	eval('if (document.getElementById("frame_' + oriTabId + '").contentWindow.confSave) { json["frame_' + oriTabId + '"] = document.getElementById("frame_' + oriTabId + '").contentWindow.confSave();}');
	eval('json = json.frame_' + oriTabId + ';');

	tabJs.Add(tabId, tabType, tabName);
	
	var tableConfLoad = setInterval(function() {
		eval('document.getElementById("frame_' + tabId + '").contentWindow.confLoad(json, true)');
		clearInterval(tableConfLoad);
	}, 1000);
}

Tab.prototype.actAdd = function (tabId, tabName) {
	var tabJs = this;

	if (!$('#manuTab #' + tabId).html()) {
		var manuTab = $('#manuTab');
		var manuBox = $('#manuBox');

		manuTab.append('<li class="nav-item"><span class="nav-link actTab" id="' + tabId + '">' + tabName + '<span class="progLoding"> <img src="./image/icon/45.gif"  /></span></span></li>');
		manuBox.append('<iframe name="frame_' + tabId + '" id="frame_' + tabId + '" frameborder="0" class="w-100" style="overflow-x:auto;display:none;height:75%;" marginwidth="0" marginheight="0" src=""></iframe>');

		$('.nav-link').off();
		$('.nav-link').on({'click': function() {
				if ($(this).attr('id')) {
					tabJs.Select($(this));
				}
			}, 'dblclick' : function() {
				if (tabJs.config.progressFl && $(this).attr('id') == tabJs.config.progressId) {
					if(confirm("실행 중인 창 입니다. 정말 닫으시겠습니까?")) {
						tabJs.Del($(this));
						tabJs.actTabInit();
					}
				}
				else {
					if ($(this).attr('id')) {
						tabJs.Del($(this));
					}
				}
			}
		});
		
		//this.Select($('#' + tabId));
	}
	else {
		$('#manuTab #' + tabId).find('.progLoding').show();
	}
}

Tab.prototype.Del = function (obj) {
	
	$('#frame_' + obj.attr('id')).remove();
	obj.parent('.nav-item').remove();
	
	if (Left(obj.attr('id'), 4) == 'act_') {
		if ($('#' + obj.attr('id').replace('act_','')).length) {
			this.Select($('#' + obj.attr('id').replace('act_','')));
		}
		else{
			if ($('.nav-item').length) {
				this.Select($('.nav-item:eq(0)').find('.nav-link'));
			}
		}
	}
	else {
		if ($('.nav-item').length) {
			this.Select($('.nav-item:eq(0)').find('.nav-link'));
		}
	}
};

Tab.prototype.Select = function (obj) {
	$('.nav-link').removeClass('active');
	obj.addClass('active');

	$('iframe').hide();
	$('#frame_' + obj.attr('id')).show();
};

Tab.prototype.Save = function () {
	var json = new Object();
	$('#manuTab .nav-item .nav-link').each(function() {
		if (!$(this).hasClass('actTab') && $('#frame_' + $(this).attr('id')).attr('tabtype') != 'excelArrange') {
			eval('json.' + $(this).attr('id') + ' = {tabType:"' + $('#frame_' + $(this).attr('id')).attr('tabtype') + '",tabName:"' + escape($(this).text()) + '"}');
		}
	});
	
	return json;
}

Tab.prototype.actTabInit = function() {
	this.config.progressFl = false;
	this.config.progressId = '';
}
