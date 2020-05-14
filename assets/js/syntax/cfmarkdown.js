/* vim:ts=4:sts=4:sw=4:
 * ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * https://www.mozilla.org/MPL/
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

define('cf/js/syntax/cfmarkdown', ['require', 'exports', 'module', 
    'ace/lib/oop', 'ace/mode/php', 'ace/mode/sql', 
    'ace/mode/text', 'ace/mode/javascript', 'ace/mode/xml', 
    'ace/mode/json', 
    'ace/mode/html', 'ace/tokenizer', 'ace/mode/markdown_highlight_rules', 
    'ace/mode/folding/markdown', './cf_php_highlight_rules', './cfmarkdown_highlight_rules'
    ],
function(require, exports, module) {
"use strict";
var oop = require("ace/lib/oop");
var TextMode = require("ace/mode/text").Mode;
var JavaScriptMode = require("ace/mode/javascript").Mode;
var XmlMode = require("ace/mode/xml").Mode;
var HtmlMode = require("ace/mode/html").Mode;
var PhpMode = require("ace/mode/php").Mode;
var PhpLangHighlightRules = require("cf/js/syntax/cf_php_highlight_rules").PhpLangHighlightRules;
var SqlMode = require("ace/mode/sql").Mode;
var JsonMode = require("ace/mode/json").Mode;
var Tokenizer = require("ace/tokenizer").Tokenizer;
var CFMarkdownHighlightRules = require("./cfmarkdown_highlight_rules").CFMarkdownHighlightRules;
var MarkdownFoldMode = require("ace/mode/folding/markdown").FoldMode;
PhpMode.$tokenizer = new Tokenizer(new PhpLangHighlightRules().getRules());

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

    this.foldingRules = new MarkdownFoldMode();
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