document.observe("dom:loaded", function() {
    /* Override back button to allow for collect in store... */
    Checkout.prototype.back = Checkout.prototype.back.wrap(function(parentMethod){
		if (this.loadWaiting) return;

	    this.accordion.sections.reverse().each(function(section) {
            if( !Element.hasClassName( section, 'active' ) && Element.hasClassName( section, 'allow' ) ) {
                this.accordion.sections.reverse();
                this.accordion.openSection( section );
                return false;
            }
        });
    });
});