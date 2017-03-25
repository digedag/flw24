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
			this.getFieldWatch(form, 'btn_watch_start').displayNone();
			this.getFieldWatch(form, 'btn_watch_stop').displayDefault();
		}
		else {
			this.getFieldWatch(form, 'btn_watch_start').displayDefault();
			this.getFieldWatch(form, 'btn_watch_stop').displayNone();
		}
		this.ticker(form);
	},
	ticker: function (form) {
		now = (new Date()).getTime();
		this.getFieldWatch(form, 'watch_localtime').setValue(now);
		start = this.getFieldWatch(form, 'watch_starttime').getValue();
		if(start > 0) {
			offset = this.trim(this.getFieldWatch(form, 'watch_offset').getValue());
			offset = parseInt(isNaN(offset) || offset == "" ? 0 : offset);
			diff = new Date(now - start);
			std = diff.getHours();
			min = diff.getMinutes() + ((std - 1) * 60) + offset;
			sec = diff.getSeconds();
			this.getFieldWatch(form, 'watch_minute').setValue(min + 1);
			this.getFieldWatch(form, 'watch').setValue(((min>9) ? min : "0" + min) + ":" + ((sec>9) ? sec : "0" + sec));
		}
		this.form = form;
		context = this;
		setTimeout(function () {
			context.ticker(context.form);
		}.bind(context), 1000);
	},
	trim: function (str) {
		return str ? str.replace(/\s+/,"") : "";
	},


});
