/* vim:ts=4:sts=4:sw=4:
 * ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *      Mihai Sucan <mihai DOT sucan AT gmail DOT com>
 *      Chris Spencer <chris.ag.spencer AT googlemail DOT com>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

(function($) {

var config = require("ace/config");
var net = require('ace/lib/net');

function autorequire(mode) {
	var module;
	try {
		module = require([mode]);
	} catch (e) {};
	if (module) 
		return module;

	var base = mode.split("/").pop();
	var filename = config.get("modePath") + "/mode-" + base + config.get("suffix");
	net.loadScript(filename, function() { });
}
autorequire("ace/mode/javascript");
autorequire("ace/mode/xml");
autorequire("ace/mode/html");
autorequire("ace/mode/php");
autorequire("ace/mode/sql");
autorequire("ace/mode/json");

define('cf/js/syntax/cfmarkdown', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/mode/text', 'ace/mode/javascript', 'ace/mode/xml', 'ace/mode/html', 'ace/tokenizer', 'ace/mode/markdown_highlight_rules'],
function(require, exports, module) {
"use strict";

var oop = require("ace/lib/oop");
var TextMode = require("ace/mode/text").Mode;
var JavaScriptMode = require("ace/mode/javascript").Mode;
var XmlMode = require("ace/mode/xml").Mode;
var HtmlMode = require("ace/mode/html").Mode;
var PhpMode = require("ace/mode/php").Mode;
var SqlMode = require("ace/mode/sql").Mode;
var JsonMode = require("ace/mode/json").Mode;
var Tokenizer = require("ace/tokenizer").Tokenizer;
var CFMarkdownHighlightRules = require("./cfmarkdown_highlight_rules").CFMarkdownHighlightRules;

var Mode = function() {
    var highlighter = new CFMarkdownHighlightRules();
    
    this.$tokenizer = new Tokenizer(highlighter.getRules());
    this.$embeds = highlighter.getEmbeds();
    this.createModeDelegates({
      "js-": JavaScriptMode,
      "xml-": XmlMode,
      "html-": HtmlMode,
      "php-": PhpMode,
      "sql-": SqlMode,
      "json-": JsonMode
    });
};
oop.inherits(Mode, TextMode);

(function() {
    this.getNextLineIndent = function(state, line, tab) {
        if (state == "listblock") {
            var match = /^((?:\s+)?)([-+*][ ]+)/.exec(line);
            if (match) {
                return new Array(match[1].length + 1).join(" ") + match[2];
            } else {
                return "";
            }
        } else {
            return this.$getIndent(line);
        }
    };
}).call(Mode.prototype);

exports.Mode = Mode;
});


define('cf/js/syntax/cfmarkdown_highlight_rules', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/mode/text_highlight_rules', 'ace/mode/javascript_highlight_rules', 'ace/mode/xml_highlight_rules', 'ace/mode/html_highlight_rules', 'ace/mode/css_highlight_rules'],
function(require, exports, module) {
"use strict";

var oop = require("ace/lib/oop");
var TextHighlightRules = require("ace/mode/text_highlight_rules").TextHighlightRules;
var JavaScriptHighlightRules = require("ace/mode/javascript_highlight_rules").JavaScriptHighlightRules;
var XmlHighlightRules = require("ace/mode/xml_highlight_rules").XmlHighlightRules;
var HtmlHighlightRules = require("ace/mode/html_highlight_rules").HtmlHighlightRules;
var CssHighlightRules = require("ace/mode/css_highlight_rules").CssHighlightRules;
var PhpHighlightRules = require("ace/mode/php_highlight_rules").PhpHighlightRules;
var SqlHighlightRules = require("ace/mode/sql_highlight_rules").SqlHighlightRules;
var JsonHighlightRules = require("ace/mode/json_highlight_rules").JsonHighlightRules;

function github_embed(tag, prefix) {
    return { // Github style block
        token : "support.function",
        regex : "^```" + tag + "\\s*$",
        next  : prefix + "start"
    };
}

var CFMarkdownHighlightRules = function() {

    // regexp must not have capturing parentheses
    // regexps are ordered -> the first match is used

    this.$rules = {
        "start" : [ {
            token : "empty_line",
            regex : '^$'
        }, 
		{
			token: "keyword",
			regex: twttr.txt.regexSupplant("(?:^|\\s)#{atSigns}[a-zA-Z0-9_]{1,20}")
		},
		{
			token: ["constant", "constant"],
			regex: twttr.txt.regexSupplant("(#{hashSigns})(#{hashtagAlphaNumeric}*#{hashtagAlpha}#{hashtagAlphaNumeric}*)")
		},
		{ // code span `
            token : ["support.function", "support.function", "support.function"],
            regex : "(`+)([^\\r]*?[^`])(\\1)"
        }, { // code block
            token : "support.function",
            regex : "^[ ]{4}.+"
        }, { // h1
            token: "markup.heading.1",
            regex: "^=+(?=\\s*$)"
        }, { // h2
            token: "markup.heading.1",
            regex: "^\\-+(?=\\s*$)"
        }, { // header
            token : function(value) {
                return "markup.heading." + value.length;
            },
            regex : "^#{1,6}"
        }, 
		github_embed("javascript", "js-"),
		github_embed("js", "js-"),
		github_embed("xml", "xml-"),
		github_embed("html", "html-"),
		github_embed("css", "css-"),
		github_embed("php", "php-"),
		github_embed("sql", "sql-"),
		github_embed("json", "json-"),
        { // Github style block
            token : "support.function",
            regex : "^```[a-zA-Z]+\\s*$",
            next  : "githubblock"
        }, { // block quote
            token : "string",
            regex : "^>[ ].+$",
            next  : "blockquote"
        }, { // reference
            token : ["text", "constant", "text", "url", "string", "text"],
            regex : "^([ ]{0,3}\\[)([^\\]]+)(\\]:\\s*)([^ ]+)(\\s*(?:[\"][^\"]+[\"])?(\\s*))$"
        }, { // link by reference
            token : ["text", "string", "text", "constant", "text"],
            regex : "(\\[)((?:[[^\\]]*\\]|[^\\[\\]])*)(\\][ ]?(?:\\n[ ]*)?\\[)(.*?)(\\])"
        }, { // link by url
            token : ["text", "string", "text", "markup.underline", "string", "text"],
            regex : "(\\[)"+
                    "(\\[[^\\]]*\\]|[^\\[\\]]*)"+
                    "(\\]\\([ \\t]*)"+
                    "(<?(?:(?:[^\\(]*?\\([^\\)]*?\\)\\S*?)|(?:.*?))>?)"+
                    "((?:[ \t]*\"(?:.*?)\"[ \\t]*)?)"+
                    "(\\))"
        }, { // HR *
            token : "constant",
            regex : "^[ ]{0,2}(?:[ ]?\\*[ ]?){3,}\\s*$"
        }, { // HR -
            token : "constant",
            regex : "^[ ]{0,2}(?:[ ]?\\-[ ]?){3,}\\s*$"
        }, { // HR _
            token : "constant",
            regex : "^[ ]{0,2}(?:[ ]?\\_[ ]?){3,}\\s*$"
        }, { // list
            token : "markup.list",
            regex : "^\\s{0,3}(?:[*+-]|\\d+\\.)\\s+",
            next  : "listblock"
        }, { // strong ** __
            token : ["string", "string", "string"],
            regex : "([*]{2}|[_]{2}(?=\\S))([^\\r]*?\\S[*_]*)(\\1)"
        }, { // emphasis * _
            token : ["string", "string", "string"],
            regex : "([*]|[_](?=\\S))([^\\r]*?\\S[*_]*)(\\1)"
        }, { // 
            token : ["text", "url", "text"],
            regex : "(<)("+
                      "(?:https?|ftp|dict):[^'\">\\s]+"+
                      "|"+
                      "(?:mailto:)?[-.\\w]+\\@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+"+
                    ")(>)"
        }, {
            token : "text",
            regex : "[^\\*_@%$`\\[#<>]+?"
        } ],
        
        "listblock" : [ { // Lists only escape on completely blank lines.
            token : "empty_line",
            regex : "^$",
            next  : "start"
        }, {
            token : "markup.list",
            regex : ".+"
        } ],
        
        "blockquote" : [ { // BLockquotes only escape on blank lines.
            token : "empty_line",
            regex : "^\\s*$",
            next  : "start"
        }, {
            token : "string",
            regex : ".+"
        } ],
        
        "githubblock" : [ {
            token : "support.function",
            regex : "^```",
            next  : "start"
        }, {
            token : "support.function",
            regex : ".+"
        } ]
    };
    this.embedRules(JavaScriptHighlightRules, "js-", [{
       token : "support.function",
       regex : "^```",
       next  : "start"
    }]);
    
    this.embedRules(HtmlHighlightRules, "html-", [{
       token : "support.function",
       regex : "^```",
       next  : "start"
    }]);
    
    this.embedRules(CssHighlightRules, "css-", [{
       token : "support.function",
       regex : "^```",
       next  : "start"
    }]);
    
    this.embedRules(XmlHighlightRules, "xml-", [{
       token : "support.function",
       regex : "^```",
       next  : "start"
    }]);

    this.embedRules(PhpHighlightRules, "php-", [{
       token : "support.function",
       regex : "^```",
       next  : "start"
    }]);

    this.embedRules(SqlHighlightRules, "sql-", [{
       token : "support.function",
       regex : "^```",
       next  : "start"
    }]);

    this.embedRules(SqlHighlightRules, "json-", [{
       token : "support.function",
       regex : "^```",
       next  : "start"
    }]);
};
oop.inherits(CFMarkdownHighlightRules, TextHighlightRules);

exports.CFMarkdownHighlightRules = CFMarkdownHighlightRules;
});
})(jQuery);
