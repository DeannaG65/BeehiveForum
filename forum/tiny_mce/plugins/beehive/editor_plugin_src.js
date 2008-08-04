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

            ed.onNodeChange.add(function(ed, cm, n) {
                cm.setActive('bhquote', n.nodeName == 'DIV' && n.className == 'quote' && !n.name);
		cm.setActive('bhcode', n.nodeName == 'PRE' && n.className == 'code' && !n.name);
		cm.setActive('bhspoiler', n.nodeName == 'DIV' && n.className == 'spoiler' && !n.name);
		cm.setActive('bhnoemots', n.nodeName == 'SPAN' && n.className == 'noemots' && !n.name);

		cm.setDisabled('bhquote', !(n.nodeName == 'DIV' && n.className == 'quote' && !n.name) && ed.selection.getContent().length == 0);
		cm.setDisabled('bhcode', !(n.nodeName == 'PRE' && n.className == 'code' && !n.name) && ed.selection.getContent().length == 0);
		cm.setDisabled('bhspoiler', !(n.nodeName == 'DIV' && n.className == 'spoiler' && !n.name) && ed.selection.getContent().length == 0);
		cm.setDisabled('bhnoemots', !(n.nodeName == 'SPAN' && n.className == 'noemots' && !n.name) && ed.selection.getContent().length == 0);
            });
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

	    if (this.inQuote()) {
	    
	        ed.dom.remove(ed.selection.getNode().previousSibling);
		ed.dom.remove(ed.selection.getNode(), true);
		return;
	    
	    }else if (ed.selection.getContent().length > 0) {
            
                var beehivePluginContainer = dom.create('div', { 'class' : 'bhplugincontainer' });
            
                var quoteText = dom.create('div', { id : 'quote', 'class' : 'quotetext' });
	        var quoteMain = dom.create('div', { 'class' : 'quote' }, ed.selection.getContent());

	        dom.add(quoteText, 'b', {}, ed.getLang('beehive.quoteText'));

                dom.add(beehivePluginContainer, quoteText);
	        dom.add(beehivePluginContainer, quoteMain);

		if (ed.selection.getContent().length == ed.getContent().length) {
   		    dom.add(beehivePluginContainer, dom.create('br'));
		}

	        ed.selection.setNode(beehivePluginContainer);
	        this.removeContainer();
	    }
        },
    
        addCode : function() {
            var ed = this.editor, dom = tinymce.DOM;

	    if (this.inCode()) {

	        ed.dom.remove(ed.selection.getNode().previousSibling);
		ed.dom.remove(ed.selection.getNode(), true);
		return;
	    
	    }else if (ed.selection.getContent().length > 0) {
            
                var beehivePluginContainer = dom.create('div', { 'class' : 'bhplugincontainer' });
    
                var codeText = dom.create('div', { id : 'code-tinymce', 'class' : 'quotetext' });    
                var codeMain = dom.create('pre', { 'class' : 'code' }, ed.selection.getContent());
    	 
                dom.add(codeText, 'b', {}, ed.getLang('beehive.codeText'));

                dom.add(beehivePluginContainer, codeText);
	        dom.add(beehivePluginContainer, codeMain);

		if (ed.selection.getContent().length == ed.getContent().length) {
   		    dom.add(beehivePluginContainer, dom.create('br'));
		}

	        ed.selection.setNode(beehivePluginContainer);
	        this.removeContainer();
	    }
        },
    
        addSpoiler : function() {
            var ed = this.editor, dom = tinymce.DOM;

	    if (this.inSpoiler()) {

	        ed.dom.remove(ed.selection.getNode().previousSibling);
		ed.dom.remove(ed.selection.getNode(), true);
		return;
	    
	    }else if (ed.selection.getContent().length > 0) {
            
                var beehivePluginContainer = dom.create('div', { 'class' : 'bhplugincontainer' });
    
                var spoilerText = dom.create('div', { id : 'spoiler', 'class' : 'quotetext' });    
                var spoilerMain = dom.create('div', { 'class' : 'spoiler' }, ed.selection.getContent());
    
                dom.add(spoilerText, 'b', {}, ed.getLang('beehive.spoilerText'));
    
                dom.add(beehivePluginContainer, spoilerText);
	        dom.add(beehivePluginContainer, spoilerMain);

		if (ed.selection.getContent().length == ed.getContent().length) {
   		    dom.add(beehivePluginContainer, dom.create('br'));
		}

	        ed.selection.setNode(beehivePluginContainer);
	        this.removeContainer();
	    }
        },
    
        addNoEmots : function() {
            var ed = this.editor, p = ed.dom.getPos(ed.dom.getParent(ed.selection.getNode(), '*'));    
	    
	    if (this.inNoEmots()) {
	        ed.dom.remove(ed.selection.getNode(), true);
	    }else if (ed.selection.getContent().length > 0) {
                ed.selection.setNode(ed.dom.create('span', { 'class' : 'noemots' }, ed.selection.getContent()));
	    }
        },
    
        openSpellCheck : function() {
            var ed = this.editor, p = ed.dom.getPos(ed.dom.getParent(ed.selection.getNode(), '*'));
            if (ed.getContent().length > 0) {
                window.open('dictionary.php?webtag=' + webtag + '&obj_id=' + this.editor.id, 'spellcheck','width=450, height=550, resizable=yes, scrollbars=yes');
            }
        },

	inQuote : function() {
	    var ed = this.editor, dom = tinymce.DOM;
	    return ed.dom.getParent(ed.selection.getNode(), function(n) {return (n.nodeName == 'DIV' && n.className == 'quote' && n.previousSibling.nodeName == 'DIV' && n.previousSibling.className == 'quotetext')});
	},

	inCode : function() {
	    var ed = this.editor, dom = tinymce.DOM;
	    return ed.dom.getParent(ed.selection.getNode(), function(n) {return (n.nodeName == 'PRE' && n.className == 'code' && n.previousSibling.nodeName == 'DIV' && n.previousSibling.className == 'quotetext')});
	},

	inSpoiler : function() {
	    var ed = this.editor, dom = tinymce.DOM;
	    return ed.dom.getParent(ed.selection.getNode(), function(n) {return (n.nodeName == 'DIV' && n.className == 'spoiler' && n.previousSibling.nodeName == 'DIV' && n.previousSibling.className == 'quotetext')});
	},

	inNoEmots : function() {
	    var ed = this.editor, dom = tinymce.DOM;
	    return ed.dom.getParent(ed.selection.getNode(), function(n) {return (n.nodeName == 'SPAN' && n.className == 'noemots')});
        },

	removeContainer : function() {
	    var ed = this.editor, dom = tinymce.DOM;
	    ed.dom.remove(ed.dom.getParent(ed.selection.getNode(), function(n) {return tinymce.DOM.hasClass(n, 'bhplugincontainer');}), true);
	}
    });

    tinymce.PluginManager.add('beehive', tinymce.plugins.beehive);

})();