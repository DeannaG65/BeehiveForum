(function() {

    tinymce.PluginManager.requireLangPack('beehive');

    tinymce.create('tinymce.plugins.beehive', {

        init : function (ed, url) {

            this.editor = ed;

            ed.addCommand('bhAddQuote', this.addQuote, this);
            ed.addCommand('bhAddCode', this.addCode, this);
            ed.addCommand('bhAddSpoiler', this.addSpoiler, this);
            ed.addCommand('bhAddNoEmots', this.addNoEmots, this);
            ed.addCommand('bhOpenSpellCheck', this.openSpellCheck, this);

            ed.addButton('bhquote', {title : 'beehive.quoteDesc', cmd : 'bhAddQuote', image : url + '/img/quote.gif'});
            ed.addButton('bhcode', {title : 'beehive.codeDesc', cmd : 'bhAddCode', image : url + '/img/code.gif'});
            ed.addButton('bhspoiler', {title : 'beehive.spoilerDesc', cmd : 'bhAddSpoiler', image : url + '/img/spoiler.gif'});
            ed.addButton('bhnoemots', {title : 'beehive.noemotsDesc', cmd : 'bhAddNoEmots', image : url + '/img/noemots.gif'});
            ed.addButton('bhspellcheck', {title : 'beehive.spellcheckDesc', cmd : 'bhOpenSpellCheck', image : url + '/img/spellcheck.gif'});
        },

        getInfo : function() {        
            return {
                longname : 'Beehive Forum TinyMCE 3.x Plugin',
                author : 'Project Beehive Forum',
                authorurl : 'http://www.beehiveforum.net',
                infourl : 'http://www.beehiveforum.net',
                version : '2.0'
            };
        },
    
        addQuote : function() {
            var ed = this.editor, dom = tinymce.DOM;

	    if (ed.selection.getContent().length > 0) {
            
                var beehivePluginContainer = dom.create('div', { 'class' : 'bhplugincontainer' });
            
                var quoteText = dom.create('div', { id : 'quote', 'class' : 'quotetext' });
	        var quoteMain = dom.create('div', { 'class' : 'quote' }, ed.selection.getContent());

	        dom.add(quoteText, 'b', {}, ed.getLang('beehive.quoteText'));

                dom.add(beehivePluginContainer, quoteText);
	        dom.add(beehivePluginContainer, quoteMain);

	        ed.selection.setNode(beehivePluginContainer);
	        this.removeContainer();
	    }
        },
    
        addCode : function() {
            var ed = this.editor, dom = tinymce.DOM;

	    if (ed.selection.getContent().length > 0) {
            
                var beehivePluginContainer = dom.create('div', { 'class' : 'bhplugincontainer' });
    
                var codeText = dom.create('div', { id : 'code-tinymce', 'class' : 'quotetext' });    
                var codeMain = dom.create('pre', { 'class' : 'code' }, ed.selection.getContent());
    	 
                dom.add(codeText, 'b', {}, ed.getLang('beehive.codeText'));

                dom.add(beehivePluginContainer, codeText);
	        dom.add(beehivePluginContainer, codeMain);

	        ed.selection.setNode(beehivePluginContainer);
	        this.removeContainer();
	    }
        },
    
        addSpoiler : function() {
            var ed = this.editor, dom = tinymce.DOM;

	    if (ed.selection.getContent().length > 0) {
            
                var beehivePluginContainer = dom.create('div', { 'class' : 'bhplugincontainer' });
    
                var spoilerText = dom.create('div', { id : 'spoiler', 'class' : 'quotetext' });    
                var spoilerMain = dom.create('div', { 'class' : 'spoiler' }, ed.selection.getContent());
    
                dom.add(spoilerText, 'b', {}, ed.getLang('beehive.spoilerText'));
    
                dom.add(beehivePluginContainer, spoilerText);
	        dom.add(beehivePluginContainer, spoilerMain);

	        ed.selection.setNode(beehivePluginContainer);
	        this.removeContainer();
	    }
        },
    
        addNoEmots : function() {
            var ed = this.editor, p = ed.dom.getPos(ed.dom.getParent(ed.selection.getNode(), '*'));    
	    
	    if (ed.selection.getContent().length > 0) {
                ed.selection.setNode(ed.dom.create('span', { 'class' : 'noemots' }, ed.selection.getContent()));
	    }
        },
    
        openSpellCheck : function() {
            var ed = this.editor, p = ed.dom.getPos(ed.dom.getParent(ed.selection.getNode(), '*'));
            if (ed.getContent().length > 0) {
                window.open('dictionary.php?webtag=' + webtag + '&obj_id=' + this.editor.id, 'spellcheck','width=450, height=550, resizable=yes, scrollbars=yes');
            }
        },

	removeContainer : function() {
	    var ed = this.editor, dom = tinymce.DOM;
	    ed.dom.remove(ed.dom.getParent(ed.selection.getNode(), function(n) {return tinymce.DOM.hasClass(n, 'bhplugincontainer');}), true);
	}
    });

    tinymce.PluginManager.add('beehive', tinymce.plugins.beehive);

})();