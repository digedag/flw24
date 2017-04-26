Formidable.Classes.tickerCB = Formidable.Classes.CodeBehindClass.extend({
	
	form: null,
	init: function() {
		this.cbTickerTypeChanged(this, this.oForm);
		this.initTicker(this.oForm);
	},
	cbTickerTypeChanged: function(parent, form) {
		var ttype = form.o('box_base__type');
		if(ttype.getValue() == 100 || ttype.getValue() == 1000) {
			form.o('box_base__box_players').displayNone();
			this.getFieldPlayerHome(form).setValue(0);
			this.getFieldPlayerGuest(form).setValue(0);
		}
		else {
			form.o('box_base__box_players').displayBlock();
		}
		this.cbSetMinute(parent, form);
	},
	cbPlayerHomeChanged: function(parent, form) {
		// your implementation here
		var field = this.getFieldPlayerGuest(form);
		field.setValue(0);
	},
	cbPlayerGuestChanged: function(parent, form) {
		// your implementation here
		var guest = this.getFieldPlayerHome(form);
		guest.setValue(0);
	},
	cbSetMinute: function(parent, form) {
		var min = this.getFieldWatch(form, 'watch_minute').getValue();
//		if(min == 0) return;

		var minuteField = form.o('box_base__minute');
		if(!minuteField.getValue()) {
			minuteField.setValue(min);
		}
	},
	cbWatchAddMinute: function(parent, form) {
		var offset = this.getCurrentOffset(form);
		this.getFieldWatch(form, 'watch_offset').setValue(offset+1);
	},
	cbWatchSubMinute: function(parent, form) {
		var offset = this.getCurrentOffset(form);
		this.getFieldWatch(form, 'watch_offset').setValue(offset-1);
	},
	getFieldPlayerHome: function(form) {
		return form.o('box_base__box_players__player_home');
	},
	getFieldPlayerGuest: function(form) {
		return form.o('box_base__box_players__player_guest');
	},

	getFieldWatch: function(form, fieldname) {
		return form.o('box_watch__' + fieldname);
	},
	
	initTicker: function(form) {
		if(this.getFieldWatch(form, 'watch_starttime').getValue() > 0) {
			if (this.isPaused(form)) {
				this.getFieldWatch(form, 'btn_watch_start').displayDefault();
				this.getFieldWatch(form, 'btn_watch_stop').displayDefault();
				this.getFieldWatch(form, 'btn_watch_pause').displayNone();
				// Spielzeit aktualisieren
				now = (new Date()).getTime();
				start = parseInt(this.getFieldWatch(form, 'watch_starttime').getValue());
				pause = parseInt(this.getFieldWatch(form, 'watch_pausetime').getValue());
				start = start + now - pause;
				this.refreshWatch(form, start, now);
			}
			else {
				this.getFieldWatch(form, 'btn_watch_start').displayNone();
				this.getFieldWatch(form, 'btn_watch_stop').displayDefault();
				this.getFieldWatch(form, 'btn_watch_pause').displayDefault();
			}
		}
		else {
			this.getFieldWatch(form, 'btn_watch_start').displayDefault();
			this.getFieldWatch(form, 'btn_watch_stop').displayNone();
			this.getFieldWatch(form, 'btn_watch_pause').displayNone();
		}
		this.ticker(form);
	},
	getCurrentOffset: function (form) {
		var offset = this.trim(this.getFieldWatch(form, 'watch_offset').getValue());
		return parseInt(isNaN(offset) || offset == '' ? 0 : offset);
	},
	isPaused: function (form) {
		var pause = this.getFieldWatch(form, 'watch_pausetime').getValue();
		pause = isNaN(pause) || pause == '' ? 0 : pause;
		return pause > 0;
	},
	ticker: function (form) {
		now = (new Date()).getTime();
		this.getFieldWatch(form, 'watch_localtime').setValue(now);
		start = this.getFieldWatch(form, 'watch_starttime').getValue();
		if(start > 0 && !this.isPaused(form)) {
			this.refreshWatch(form, start, now);
		}
		this.form = form;
		context = this;
		setTimeout(function () {
			context.ticker(context.form);
		}.bind(context), 1000);
	},
	refreshWatch: function (form, start, now) {
//		var matchPart = parseInt(this.getFieldWatch(form, 'watch_matchpart').getValue());
		var matchPart = 0;
		offset = this.getCurrentOffset(form);
		offset = offset + matchPart;
		diff = new Date(now - start);
		std = diff.getHours();
		min = diff.getMinutes() + ((std - 1) * 60) + offset;
		if(min < 0) {
			// Zeit darf nicht negativ werden
			min = 0;
			this.getFieldWatch(form, 'watch_offset').setValue(offset+1);
		}
		sec = diff.getSeconds();
		this.getFieldWatch(form, 'watch_minute').setValue(min + 1);
		var watchField = this.getFieldWatch(form, 'watch');
		if( typeof watchField.setHtml == 'function') {
			watchField.setHtml(((min>9) ? min : "0" + min) + ":" + ((sec>9) ? sec : "0" + sec));
		}
		else {
			watchField.setValue(((min>9) ? min : "0" + min) + ":" + ((sec>9) ? sec : "0" + sec));
		}
	},
	trim: function (str) {
		return str ? str.replace(/\s+/,"") : "";
	},


});
