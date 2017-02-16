Formidable.Classes.tickerCB = Formidable.Classes.CodeBehindClass.extend({

	init: function() {
		this.cbTickerTypeChanged(this, this.oForm);
	},
	cbTickerTypeChanged: function(parent, form) {
		var ttype = form.o('box_base__type');
		if(ttype.getValue() == 100) {
			form.o('box_base__box_players').displayNone();
			this.getFieldPlayerHome(form).setValue(0);
			this.getFieldPlayerGuest(form).setValue(0);
		}
		else {
			form.o('box_base__box_players').displayBlock();
		}
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
	getFieldPlayerHome: function(form) {
		return form.o('box_base__box_players__player_home');
	},
	getFieldPlayerGuest: function(form) {
		return form.o('box_base__box_players__player_guest');
	}

});
