Formidable.Classes.tickerCB = Formidable.Classes.CodeBehindClass.extend({
	
	form: null,
	init: function() {
		this.cbTickerTypeChanged(this, this.oForm);
		this.initTicker(this.oForm);
	},
	cbTickerTypeChanged: function(parent, form) {
		var ttype = this.getFieldBase(form, 'type');
		if(ttype.getValue() == 100 || ttype.getValue() == 1000) {
			this.closeBoxPlayers(form);
			this.closeBoxChange(form);
		}
		else if (ttype.getValue() == 80) {
			// Spielerwechsel
			this.openBoxChange(form);
			this.closeBoxPlayers(form);
		}
		else {
			this.closeBoxChange(form);
			this.openBoxPlayers(form);
		}
		this.cbSetMinute(parent, form);
	},

	closeBoxPlayers: function(form) {
		this.getFieldBase(form, 'box_players').displayNone();
		this.getFieldPlayerHome(form).setValue(0);
		this.getFieldPlayerGuest(form).setValue(0);
	},
	openBoxPlayers: function(form) {
		this.getFieldBase(form, 'box_players').displayBlock();
	},
	closeBoxChange: function(form) {
		this.getFieldBase(form, 'box_change').displayNone();
	},
	openBoxChange: function(form) {
		this.getFieldBase(form, 'box_change').displayBlock();
	},

	cbPlayerHomeChangeChanged: function(parent, form) {
		var field = this.getFieldBoxChange(form, 'tab_guest__player_guest_changein');
		field.setValue(0);
		var field = this.getFieldBoxChange(form, 'tab_guest__player_guest_changeout');
		field.setValue(0);
	},
	cbPlayerGuestChangeChanged: function(parent, form) {
		var field = this.getFieldBoxChange(form, 'tab_home__player_home_changein');
		field.setValue(0);
		var field = this.getFieldBoxChange(form, 'tab_home__player_home_changeout');
		field.setValue(0);
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
/*
		var minuteField = form.o('box_base__minute');
		if(!minuteField.getValue()) {
			minuteField.setValue(min);
 		}
*/
		
		var minuteField = this.getFieldBase(form, 'minute');
		if(minuteField.getValue()) {
			return;
		}
		var min = this.getFieldWatch(form, 'watch_minute').getValue();
		if(min == '') return;
		min = parseInt(min);
		// in der ersten Halbzeit nach der 45 den Offset setzen
		if(this.isMatchPart(1, form) && min > 45) {
			var extraTime = min - 45;
			min = min - extraTime;
			this.getFieldBase(form, 'extra_time').setValue(extraTime);
		}
		else if(this.isMatchPart(2, form) && min > 90) {
			var extraTime = min - 90;
			min = min - extraTime;
			this.getFieldBase(form, 'extra_time').setValue(extraTime);
		}
		else if(this.isMatchPart(3, form) && min > 105) {
			var extraTime = min - 105;
			min = min - extraTime;
			this.getFieldBase(form, 'extra_time').setValue(extraTime);
		}
		minuteField.setValue(min);
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
		return form.o('tab_ticker__box_base__box_players__player_home');
	},
	getFieldPlayerGuest: function(form) {
		return form.o('tab_ticker__box_base__box_players__player_guest');
	},

	getFieldBoxChange: function(form, fieldname) {
		return form.o('tab_ticker__box_base__box_change__' + fieldname);
	},

	getFieldWatch: function(form, fieldname) {
		return form.o('tab_ticker__box_watch__' + fieldname);
	},
	getFieldBase: function(form, fieldname) {
		return form.o('tab_ticker__box_base__' + fieldname);
	},
	
	initTicker: function(form) {
		if(this.getFieldWatch(form, 'watch_starttime').getValue() > 0) {
			if (this.isPaused(form)) {
				// Spielzeit aktualisieren
				now = (new Date()).getTime();
				start = parseInt(this.getFieldWatch(form, 'watch_starttime').getValue());
				pause = parseInt(this.getFieldWatch(form, 'watch_pausetime').getValue());
				start = start + now - pause;
				this.refreshWatch(form, start, now);


				if (parseInt(this.getFieldWatch(form, 'watch_paused').getValue())) {
					// Uhr während des Spiels angehalten
					this.getFieldWatch(form, 'btn_watch_start').displayDefault();
					this.getFieldWatch(form, 'btn_watch_stop').displayNone();
				}
				else {
					if(this.isHalftimePause(form)) {
						console.info('Paused im SPiel: ' + this.isPaused(form));
						// Halbzeitpause läuft
						this.getFieldWatch(form, 'btn_watch_start').displayNone();
						this.getFieldWatch(form, 'btn_watch_stop').displayNone();
						this.getFieldWatch(form, 'btn_watch_secondht').displayDefault();
						
					}
					else if(this.isExtraTimePause(form)) {
						// Pause vor Verlängerung läuft
						this.getFieldWatch(form, 'btn_watch_start').displayNone();
						this.getFieldWatch(form, 'btn_watch_stop').displayNone();
						this.getFieldWatch(form, 'btn_watch_extratime_1').displayDefault();
					}
					else if(this.isExtraTimeHTPause(form)) {
						// Halbzeitpause Verlängerung läuft
						this.getFieldWatch(form, 'btn_watch_start').displayNone();
						this.getFieldWatch(form, 'btn_watch_stop').displayNone();
						this.getFieldWatch(form, 'btn_watch_extratime_2').displayDefault();
					}
					else if(this.isPenaltiesPause(form)) {
						// Halbzeitpause Verlängerung läuft
						this.getFieldWatch(form, 'btn_watch_start').displayNone();
						this.getFieldWatch(form, 'btn_watch_stop').displayDefault();
					}
					
				}
				this.getFieldWatch(form, 'btn_watch_pause').displayNone();
			}
			else {
				this.getFieldWatch(form, 'btn_watch_start').displayNone();
				if(this.isMatchPart(1, form)) {
					this.getFieldWatch(form, 'btn_watch_stop').displayNone();
					this.getFieldWatch(form, 'btn_watch_halftime').displayDefault();
				}
				else if(this.isMatchPart(2, form)) {
					this.getFieldWatch(form, 'btn_watch_stop').displayDefault();
					this.getFieldWatch(form, 'btn_watch_extratime').displayDefault();
					this.getFieldWatch(form, 'btn_watch_halftime').displayNone();
				}
				else if(this.isMatchPart(3, form)) {
					this.getFieldWatch(form, 'btn_watch_stop').displayNone();
					this.getFieldWatch(form, 'btn_watch_pause').displayDefault();
					this.getFieldWatch(form, 'btn_watch_extratime_ht').displayDefault();
				}
				else {
					this.getFieldWatch(form, 'btn_watch_penalties').displayDefault();
					this.getFieldWatch(form, 'btn_watch_stop').displayDefault();
					this.getFieldWatch(form, 'btn_watch_halftime').displayNone();
				}
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
	getCurrentMatchPart: function (form) {
		var matchpart = this.trim(this.getFieldWatch(form, 'watch_matchpart').getValue());
		return parseInt(matchpart) || 0;
	},
	getCurrentMinute: function (form) {
		var value = this.trim(this.getFieldWatch(form, 'watch_minute').getValue());
		return parseInt(value) || 0;
	},
	isMatchPart: function (part, form) {
		if (part == 1) {
			return this.getCurrentMatchPart(form) < 45;
		}
		else if(part == 2) {
			return this.getCurrentMatchPart(form) == 45;
		}
		else if(part == 3) {
			return this.getCurrentMatchPart(form) == 90;
		}
		else if(part == 4) {
			return this.getCurrentMatchPart(form) == 105;
		}
		return false;
	},
	isHalftimePause: function (form) {
		return this.isPaused(form) && this.getCurrentMatchPart(form) == 45 && 
			this.getFieldWatch(form, 'watch_minute').getValue() == 46;

	},
	isExtraTimePause: function (form) {
		return this.isPaused(form) && this.getCurrentMatchPart(form) == 90 && 
			this.getFieldWatch(form, 'watch_minute').getValue() == 91;
	},
	isExtraTimeHTPause: function (form) {
		return this.isPaused(form) && this.getCurrentMatchPart(form) == 105 && 
			this.getFieldWatch(form, 'watch_minute').getValue() == 106;
	},
	isPenaltiesPause: function (form) {
		return this.isPaused(form) && this.getCurrentMatchPart(form) == 121;
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
		var matchPart = this.getCurrentMatchPart(form);
//		var matchPart = 0;
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
