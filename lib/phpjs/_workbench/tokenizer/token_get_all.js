function token_get_all (source) {
	// Split given source into PHP tokens
	// + original by: Marco Marchiò
	// + improved by: Brett Zamir (http://brett-zamir.me)
	// - depends on: token_name
	// % note 1: Token numbers depend on the PHP version
	// % note 2: token_name is only necessary for a non-standard php.js-specific use of this function;
	// % note 2: if you define an object on this.php_js.phpParser (where "this" is the scope of the
	// % note 2: token_get_all function (either a namespaced php.js object or the window object)),
	// % note 2: this function will call that object's methods if they have the same names as the tokens,
	// % note 2: passing them the string, line number, and token number (in that order)
	// * example 1: token_get_all('/'+'* comment *'+'/');
	// * returns 1: [[310, '/* comment */', 1]]
	
	// Token to number conversion
    var num,
        nextch,
        word,
        ch,
        parts,
        sym,
        ASCII,
        i = 0,
        that = this,
        length = source.length,
        //Regexp to check if the characters that follow a word are valid as heredoc end declaration
        heredocEndFollowing = /^;?\r?\n/,
        tokens = {
            T_REQUIRE_ONCE: 261,
            T_REQUIRE: 260,
            T_EVAL: 259,
            T_INCLUDE_ONCE: 258,
            T_INCLUDE: 257,
            T_LOGICAL_OR: 262,
            T_LOGICAL_XOR: 263,
            T_LOGICAL_AND: 264,
            T_PRINT: 265,
            T_SR_EQUAL: 276,
            T_SL_EQUAL: 275,
            T_XOR_EQUAL: 274,
            T_OR_EQUAL: 273,
            T_AND_EQUAL: 272,
            T_MOD_EQUAL: 271,
            T_CONCAT_EQUAL: 270,
            T_DIV_EQUAL: 269,
            T_MUL_EQUAL: 268,
            T_MINUS_EQUAL: 267,
            T_PLUS_EQUAL: 266,
            T_BOOLEAN_OR: 277,
            T_BOOLEAN_AND: 278,
            T_IS_NOT_IDENTICAL: 282,
            T_IS_IDENTICAL: 281,
            T_IS_NOT_EQUAL: 280,
            T_IS_EQUAL: 279,
            T_IS_GREATER_OR_EQUAL: 284,
            T_IS_SMALLER_OR_EQUAL: 283,
            T_SR: 286,
            T_SL: 285,
            T_INSTANCEOF: 287,
            T_UNSET_CAST: 296,
            T_BOOL_CAST: 295,
            T_OBJECT_CAST: 294,
            T_ARRAY_CAST: 293,
            T_STRING_CAST: 292,
            T_DOUBLE_CAST: 291,
            T_INT_CAST: 290,
            T_DEC: 289,
            T_INC: 288,
            T_CLONE: 298,
            T_NEW: 297,
            T_EXIT: 299,
            T_IF: 300,
            T_ELSEIF: 301,
            T_ELSE: 302,
            T_ENDIF: 303,
            T_LNUMBER: 304,
            T_DNUMBER: 305,
            T_STRING: 306,
            T_STRING_VARNAME: 307,
            T_VARIABLE: 308,
            T_NUM_STRING: 309,
            T_INLINE_HTML: 310,
            T_CHARACTER: 311,
            T_BAD_CHARACTER: 312,
            T_ENCAPSED_AND_WHITESPACE: 313,
            T_CONSTANT_ENCAPSED_STRING: 314,
            T_ECHO: 315,
            T_DO: 316,
            T_WHILE: 317,
            T_ENDWHILE: 318,
            T_FOR: 319,
            T_ENDFOR: 320,
            T_FOREACH: 321,
            T_ENDFOREACH: 322,
            T_DECLARE: 323,
            T_ENDDECLARE: 324,
            T_AS: 325,
            T_SWITCH: 326,
            T_ENDSWITCH: 327,
            T_CASE: 328,
            T_DEFAULT: 329,
            T_BREAK: 330,
            T_CONTINUE: 331,
            T_GOTO: 332,
            T_FUNCTION: 333,
            T_CONST: 334,
            T_RETURN: 335,
            T_TRY: 336,
            T_CATCH: 337,
            T_THROW: 338,
            T_USE: 339,
            T_GLOBAL: 340,
            T_PUBLIC: 346,
            T_PROTECTED: 345,
            T_PRIVATE: 344,
            T_FINAL: 343,
            T_ABSTRACT: 342,
            T_STATIC: 341,
            T_VAR: 347,
            T_UNSET: 348,
            T_ISSET: 349,
            T_EMPTY: 350,
            T_HALT_COMPILER: 351,
            T_CLASS: 352,
            T_INTERFACE: 353,
            T_EXTENDS: 354,
            T_IMPLEMENTS: 355,
            T_OBJECT_OPERATOR: 356,
            T_DOUBLE_ARROW: 357,
            T_LIST: 358,
            T_ARRAY: 359,
            T_CLASS_C: 360,
            T_METHOD_C: 361,
            T_FUNC_C: 362,
            T_LINE: 363,
            T_FILE: 364,
            T_COMMENT: 365,
            T_DOC_COMMENT: 366,
            T_OPEN_TAG: 367,
            T_OPEN_TAG_WITH_ECHO: 368,
            T_CLOSE_TAG: 369,
            T_WHITESPACE: 370,
            T_START_HEREDOC: 371,
            T_END_HEREDOC: 372,
            T_DOLLAR_OPEN_CURLY_BRACES: 373,
            T_CURLY_OPEN: 374,
            T_PAAMAYIM_NEKUDOTAYIM: 375,
            T_NAMESPACE: 376,
            T_NS_C: 377,
            T_DIR: 378,
            T_NS_SEPARATOR: 379
        },
        //Keywords tokens
        keywordsToken = {
            "abstract": tokens.T_ABSTRACT,
            "array": tokens.T_ARRAY,
            "as": tokens.T_AS,
            "break": tokens.T_BREAK,
            "case": tokens.T_CASE,
            "catch": tokens.T_CATCH,
            "class": tokens.T_CLASS,
            "__CLASS__": tokens.T_CLASS_C,
            "clone": tokens.T_CLONE,
            "const": tokens.T_CONST,
            "continue": tokens.T_CONTINUE,
            "declare": tokens.T_DECLARE,
            "default": tokens.T_DEFAULT,
            "__DIR__": tokens.T_DIR,
            "die": tokens.T_EXIT,
            "do": tokens.T_DO,
            "echo": tokens.T_ECHO,
            "else": tokens.T_ELSE,
            "elseif": tokens.T_ELSEIF,
            "empty": tokens.T_EMPTY,
            "enddeclare": tokens.T_ENDDECLARE,
            "endfor": tokens.T_ENDFOR,
            "endforeach": tokens.T_ENDFOREACH,
            "endif": tokens.T_ENDIF,
            "endswitch": tokens.T_ENDSWITCH,
            "endwhile": tokens.T_ENDWHILE,
            "eval": tokens.T_EVAL,
            "exit": tokens.T_EXIT,
            "extends": tokens.T_EXTENDS,
            "__FILE__": tokens.T_FILE,
            "final": tokens.T_FINAL,
            "for": tokens.T_FOR,
            "foreach": tokens.T_FOREACH,
            "function": tokens.T_FUNCTION,
            "__FUNCTION__": tokens.T_FUNC_C,
            "global": tokens.T_GLOBAL,
            "goto": tokens.T_GOTO,
            "__halt_compiler": tokens.T_HALT_COMPILER,
            "if": tokens.T_IF,
            "implements": tokens.T_IMPLEMENTS,
            "include": tokens.T_INCLUDE,
            "include_once": tokens.T_INCLUDE_ONCE,
            "instanceof": tokens.T_INSTANCEOF,
            "interface": tokens.T_INTERFACE,
            "isset": tokens.T_ISSET,
            "__LINE__": tokens.T_LINE,
            "list": tokens.T_LIST,
            "and": tokens.T_LOGICAL_AND,
            "or": tokens.T_LOGICAL_OR,
            "xor": tokens.T_LOGICAL_XOR,
            "__METHOD__": tokens.T_METHOD_C,
            "namespace": tokens.T_NAMESPACE,
            "__NAMESPACE__": tokens.T_NS_C,
            "new": tokens.T_NEW,
            "print": tokens.T_PRINT,
            "private": tokens.T_PRIVATE,
            "public": tokens.T_PUBLIC,
            "protected": tokens.T_PROTECTED,
            "require": tokens.T_REQUIRE,
            "require_once": tokens.T_REQUIRE_ONCE,
            "return": tokens.T_RETURN,
            "static": tokens.T_STATIC,
            "switch": tokens.T_SWITCH,
            "throw": tokens.T_THROW,
            "try": tokens.T_TRY,
            "unset": tokens.T_UNSET,
            "use": tokens.T_USE,
            "var": tokens.T_VAR,
            "while": tokens.T_WHILE
        },
        //Type casting tokens
        typeCasting = {
            "array": tokens.T_ARRAY_CAST,
            "bool": tokens.T_BOOL_CAST,
            "boolean": tokens.T_BOOL_CAST,
            "real": tokens.T_DOUBLE_CAST,
            "double": tokens.T_DOUBLE_CAST,
            "float": tokens.T_DOUBLE_CAST,
            "int": tokens.T_INT_CAST,
            "integer": tokens.T_INT_CAST,
            "object": tokens.T_OBJECT_CAST,
            "string": tokens.T_STRING_CAST,
            "unset": tokens.T_UNSET_CAST,
            "binary": tokens.T_STRING_CAST
        },
        //Symbols tokens with 2 characters
        symbols2chars = {
            "&=": tokens.T_AND_EQUAL,
            "&&": tokens.T_BOOLEAN_AND,
            "||": tokens.T_BOOLEAN_OR,
            "?>": tokens.T_CLOSE_TAG,
            "%>": tokens.T_CLOSE_TAG,
            ".=": tokens.T_CONCAT_EQUAL,
            "--": tokens.T_DEC,
            "/=": tokens.T_DIV_EQUAL,
            "=>": tokens.T_DOUBLE_ARROW,
            "::": tokens.T_PAAMAYIM_NEKUDOTAYIM,
            "++": tokens.T_INC,		
            "==": tokens.T_IS_EQUAL,
            ">=": tokens.T_IS_GREATER_OR_EQUAL,
            "!=": tokens.T_IS_NOT_EQUAL,
            "<>": tokens.T_IS_NOT_EQUAL,
            "<=": tokens.T_IS_SMALLER_OR_EQUAL,
            "-=": tokens.T_MINUS_EQUAL,
            "%=": tokens.T_MOD_EQUAL,
            "*=": tokens.T_MUL_EQUAL,
            "->": tokens.T_OBJECT_OPERATOR,
            "|=": tokens.T_OR_EQUAL,
            "+=": tokens.T_PLUS_EQUAL,
            "<<": tokens.T_SL,
            ">>": tokens.T_SR,
            "^=": tokens.T_XOR_EQUAL,
            "<?": tokens.T_OPEN_TAG
        },
        //Symbols tokens with 3 characters
        symbols3chars = {
            "===": tokens.T_IS_IDENTICAL,	
            "!==": tokens.T_IS_NOT_IDENTICAL,		
            "<<=": tokens.T_SL_EQUAL,
            ">>=": tokens.T_SR_EQUAL,
            "<?=": tokens.T_OPEN_TAG_WITH_ECHO,
            "<%=": tokens.T_OPEN_TAG_WITH_ECHO
        },
        //Buffer tokens
        bufferTokens = {
            "html": tokens.T_INLINE_HTML,
            "inlineComment": tokens.T_COMMENT,
            "comment": tokens.T_COMMENT,
            "docComment": tokens.T_DOC_COMMENT,
            "singleQuote": tokens.T_CONSTANT_ENCAPSED_STRING,
            "doubleQuotes": tokens.T_CONSTANT_ENCAPSED_STRING,
            "nowdoc": tokens.T_ENCAPSED_AND_WHITESPACE,
            "heredoc": tokens.T_ENCAPSED_AND_WHITESPACE
        },
        //Characters that are emitted as tokens without a code
        singleTokenChars = ";(){}[],~@`=+/-*.$|^&<>%!?:\"'\\",
        //Buffer type. Start an html buffer immediatelly.
        bufferType = "html",
        //Buffer content
        buffer = "",
        //Last emitted token
        lastToken,
        //Results array
        ret = [],
        //Word that started the heredoc or nowdoc buffer
        heredocWord,
        //Line number
        line = 1,
        //Line at which the buffer begins
        lineBuffer = 1,
        //Flag that indicates if the current double quoted string has been splitted
        split,
        //This variable will store the previous buffer type of the tokenizer before parsing a
        //complex variable syntax
        complexVarPrevBuffer,
        //Number of open brackets inside a complex variable syntax
        openBrackets,
        //Function to emit tokens
        emitToken = function (token, code, preventBuffer, l) {
            if (!preventBuffer && bufferType) {
                buffer += token;
                lastToken = null;
            } else {
                lastToken = code || token;
                ret.push(code ? [code, token, l || line] : token);
            }
        },
        //Function to emit and close the current buffer
        emitBuffer = function () {
            buffer && emitToken(buffer, bufferTokens[bufferType], true, lineBuffer);
            buffer = "";
            bufferType = null;
        },
        //Function to check if the token at the current index is escaped
        isEscaped = function (s) {
            var escaped = false,
                c = (s || i) - 1;
            for (; c >= 0; c--) {
                if (source.charAt(c) !== "\\") {
                    break;
                }
                escaped = !escaped;
            }
            return escaped;
        },
        //Returns the number of line feed characters in the given string
        countNewLines = function (str) {
            var i = 0;
            str.replace(/\n/g, function () {
                i++;
            });
            return i;
        },
        //Get the part of source that is between the current index and the index of the limit character
        getBufferAndEmit = function (start, type, limit, canBeEscaped) {
            /*23456*/
            var startL = start.length,
                startPos = i + startL,
                pos = source.indexOf(limit, startPos);
            lineBuffer = line;
            if (canBeEscaped) {
                while (pos !== -1 && isEscaped(pos)) {
                    pos = source.indexOf(limit, pos + 1);
                }
            }
            bufferType = type;
            if (pos === -1) {
                buffer = start + source.substr(startPos);
            } else {
                buffer = start + source.substr(startPos, pos - startPos) + limit;
            }
            line += countNewLines(buffer);
            emitBuffer();
            i = pos + limit.length - 1;
        },
        //This function is used to split a double quoted string or a heredoc buffer after a variable
        //has been found inside it
        splitString = function () {
            //Don't emit empty buffers
            if (!buffer) {
                return;
            }
            //If the buffer is a double quoted string and it has not yet been splitted, emit the double
            //quotes as a token without an associated code
            if (bufferType === "doubleQuotes" && !split) {
                split = true;
                emitToken('"', null, true);
                buffer = buffer.substr(1);
            }
            buffer && emitToken(buffer, tokens.T_ENCAPSED_AND_WHITESPACE, true, lineBuffer);
            buffer = "";
            lineBuffer = line;
        },
        //Checks if the given ASCII identifies a whitespace
        isWhitespace = function (ASCII) {
            return ASCII === 9 || ASCII === 10 || ASCII === 13 || ASCII === 32;
        },
        //Get next whitespaces
        getWhitespaces = function () {
            var as,
                chr,
                ret = "";
            for (i++; i < length; i++) {
                chr = source.charAt(i);
                as = chr.charCodeAt(0);
                if (isWhitespace(as)) {
                    ret += chr;
                } else {
                    i--;
                    break;
                }
            }
            return ret;
        },
        //Get next word
        getWord = function (i) {
            var match = /^[a-zA-Z_]\w*/.exec(source.substr(i));
            return match ? match[0] : null;
        },
        //Get next heredoc declaration
        getHeredocWord = function () {
            return (/^<<< *(['"]?[a-zA-Z]\w*)['"]?\r?\n/).exec(source.substr(i));
        },
        //Get next type casting declaration
        getTypeCasting = function () {
            var match = (/^\( *([a-zA-Z]+) *\)/).exec(source.substr(i));
            return match && match[1] && (match[1].toLowerCase()) in typeCasting ? match : null;
        },
        //Get next php long open declaration
        getLongOpenDeclaration = function (i) {
            return (/^php(?:\r?\s)?/i).exec(source.substr(i));
        },
        //Get next integer or float number
        getNumber = function () {
            var rnum = /^(?:((?:\d+(?:\.\d*)?|\d*\.\d+)[eE][\+\-]?\d+|\d*\.\d+|\d+\.\d*)|(\d+(?:x[0-9a-fA-F]+)?))/,
                match = rnum.exec(source.substr(i));
            if (!match) {
                return null;
            }
            if (match[2]) {
                var isHex = match[2].toLowerCase().indexOf("x") > -1;
                //If it's greater than 2147483648 it's considered as a floating point number
                if (parseInt(isHex ? parseInt(match[2], 16) : match[2], 10) < 2147483648) {
                    return [match[2], tokens.T_LNUMBER];
                }
                return [match[2], tokens.T_DNUMBER];
            }
            return [match[1], tokens.T_DNUMBER];
        };

    // Avoid running a conditional for each token by overwriting function
    if (this.php_js && this.php_js.phpParser) {
        var oldEmitToken = emitToken;
        emitToken = function (token, code, preventBuffer, l) {
            var action = that.php_js.phpParser[typeof token === 'number' ? that.token_name(token) : token];
            // Allow execution of (optional) parsing callbacks during first run-through
            if (typeof action === 'function') {
                action.call(that.php_js.phpParser, code, line, token, preventBuffer, l);
            }
            oldEmitToken(token, code, preventBuffer, l);
        };
    }
	
	for (; i < length; i++) {
		ch = source.charAt(i);
		ASCII = ch.charCodeAt(0);
		//Whitespaces
		if (isWhitespace(ASCII)) {
			//Get next whitespaces too
			ch += getWhitespaces();
			emitToken(ch, tokens.T_WHITESPACE);
			line += countNewLines(ch);
		} else if (ch === "#" || (ch === "/" && ((nextch = source.charAt(i + 1)) === "*" || nextch === "/"))) {
			//Comment signs
			//Change the buffer only if there's no active buffer
			if (!bufferType) {
				if (ch === "#") {
					getBufferAndEmit("#", "inlineComment", "\n");
				} else if (ch + nextch === "//") {
					getBufferAndEmit("//", "inlineComment", "\n");
				} else if ((ch + nextch + source.charAt(i + 2)) === "/**") {
					getBufferAndEmit(
						"/**",
						//It's a doc comment only if it's followed by a whitespaces
						isWhitespace(source.charCodeAt(i + 3)) ? "docComment" : "comment",
						"*/"
					);
				} else {
					getBufferAndEmit("/*", "comment", "*/");
				}
				continue;
			}
			emitToken(ch);
		} else if (ch === "$" && (word = getWord(i + 1))) {
			//Variable
			if ((bufferType === "heredoc" || bufferType === "doubleQuotes") && !isEscaped()) {
				splitString();
				emitToken(ch + word, tokens.T_VARIABLE, true);
			} else {
				emitToken(ch + word, tokens.T_VARIABLE);
			}
			i += word.length;
		} else if (ch === "<" && source.substr(i + 1, 2) === "<<" && (word = getHeredocWord())) {
			//Heredoc and nowdoc start declaration
			emitToken(word[0], tokens.T_START_HEREDOC);
			line++;
			if (!bufferType) {
				heredocWord = word[1];
				//If the first character is a quote then it's a nowdoc otherwise it's an heredoc
				if (heredocWord.charAt(0) === "'") {
					//Strip the leading quote
					heredocWord = heredocWord.substr(1);
					bufferType = "nowdoc";
				} else {
					if (heredocWord.charAt(0) === '"') {
						heredocWord = heredocWord.substr(1);
					}
					bufferType = "heredoc";
				}
				lineBuffer = line;
			}
			i += word[0].length - 1;
		} else if (ch === "(" && (word = getTypeCasting())) {
			//Type-casting
			emitToken(word[0], typeCasting[word[1].toLowerCase()]);
			i += word[0].length - 1;
		} else if ((ch === "." || (ch >= "0" && ch <= "9")) && (num = getNumber())) {
			//Numbers
			//Numeric array index inside a heredoc or a double quoted string
			if (lastToken === "[" && (bufferType === "heredoc" || bufferType === "doubleQuotes")) {
				emitToken(num[0], tokens.T_NUM_STRING, true);
			} else {
				emitToken(num[0], num[1]);
			}
			i += String(num[0]).length - 1;
		} else if (singleTokenChars.indexOf(ch) > -1) {
			//Symbols
			sym = source.substr(i, 3);
			if (sym in symbols3chars) {
				i += 2;
				//If it's a php open tag emit the html buffer
				if (bufferType === "html" && symbols3chars[sym] === tokens.T_OPEN_TAG_WITH_ECHO) {
					emitBuffer();
				}
				emitToken(sym, symbols3chars[sym]);
				continue;
			}
			sym = ch + source.charAt(i + 1);
			if (sym in symbols2chars) {
				//If it's a php open tag check if it's written in the long form and emit the html buffer
				if (symbols2chars[sym] === tokens.T_OPEN_TAG && bufferType === "html") {
					emitBuffer();
					i++;
					if (word = getLongOpenDeclaration(i + 1)) {
						i += word[0].length;
						sym += word[0];
					}
					emitToken(sym, tokens.T_OPEN_TAG);
					if (sym.indexOf("\n") > -1) {
						line++;
					}
					continue;
				}
				i++;
				//Syntax $obj->prop inside strings and heredoc
				if (sym === "->" && lastToken === tokens.T_VARIABLE && (bufferType === "heredoc" ||
					bufferType === "doubleQuotes")) {
					emitToken(sym, symbols2chars[sym], true);
					continue;
				}
				emitToken(sym, symbols2chars[sym]);
				//If the token is a PHP close tag and there isn't an active buffer start an html buffer
				if (!bufferType && symbols2chars[sym] === tokens.T_CLOSE_TAG) {
					//PHP closing tag includes the following new line characters
					if (nextch = /^\r?\n/.exec(source.substr(i + 1, 2))) {
						ret[ret.length - 1][1] += nextch[0];
						i += nextch[0].length;
						line++;
					}
					bufferType = "html";
					lineBuffer = line;
				}
				continue;
			}
			//Start string buffers if there isn't an active buffer and the character is a quote
			if (!bufferType && (ch === "'" || ch === '"')) {
				if (ch === "'") {
					getBufferAndEmit("'", "singleQuote", "'", true);
				} else {
					split = false;
					bufferType = "doubleQuotes";
					lineBuffer = line;
					//Add the token to the buffer and continue to skip next checks
					emitToken(ch);
				}
				continue;
			} else if (ch === '"' && bufferType === "doubleQuotes" && !isEscaped()) {
				//If the string has been splitted emit the current buffer and the double quotes
				//as separate tokens
				if (split) {
					splitString();
					bufferType = null;
					emitToken('"');
				} else {
					emitToken('"');
					emitBuffer();
				}
				continue;
			} else if (bufferType === "heredoc" || bufferType === "doubleQuotes") {
				//Array index delimiters inside heredoc or double quotes
				if ((ch === "[" && lastToken === tokens.T_VARIABLE) ||
                        (ch === "]" && (lastToken === tokens.T_NUM_STRING ||
                        lastToken === tokens.T_STRING))) {
					emitToken(ch, null, true);
					continue;
				} else if (((ch === "$" && source.charAt(i + 1) === "{") ||
							(ch === "{" && source.charAt(i + 1) === "$")) &&
							!isEscaped()) {
					//Complex variable syntax ${varname} or {$varname}. Store the current
					//buffer type and evaluate next tokens as there's no active buffer.
					//The current buffer will be reset when the declaration is closed
					splitString();
					complexVarPrevBuffer = bufferType;
					bufferType = null;
					if (ch === "$") {
						emitToken(ch + "{", tokens.T_DOLLAR_OPEN_CURLY_BRACES);
						i++;
					} else {
						emitToken(ch, tokens.T_CURLY_OPEN);
					}
					openBrackets = 1;
					continue;
				}
			} else if (ch === "\\") {
				//Namespace separator
				emitToken(ch, tokens.T_NS_SEPARATOR);
				continue;
			}
			emitToken(ch);
			//Increment or decrement the number of open brackets inside a complex
			//variable syntax
			if (complexVarPrevBuffer && (ch === "{" || ch === "}")) {
				if (ch === "{") {
					openBrackets++;
				} else if (!--openBrackets) {
					//If every bracket has been closed reset the previous buffer
					bufferType = complexVarPrevBuffer;
					complexVarPrevBuffer = null;
				}
			}
		} else if (word = getWord(i)) {
			//Words
			var wordLower = word.toLowerCase();
			//Check to see if it's a keyword
			if (keywordsToken.hasOwnProperty(word) || keywordsToken.hasOwnProperty(wordLower)) {
				//If it's preceded by -> than it's an object property and it must be tokenized as T_STRING
				emitToken(
					word,
					lastToken === tokens.T_OBJECT_OPERATOR ?
                        tokens.T_STRING :
                        keywordsToken[word] || keywordsToken[wordLower]
				);
				i += word.length - 1;
				continue;
			}
			//Stop the heredoc or the nowdoc if it's the word that has generated it
			if ((bufferType === "nowdoc" || bufferType === "heredoc") && word === heredocWord &&
                    source.charAt(i - 1) === "\n" &&
                    heredocEndFollowing.test(source.substr(i + word.length))) {
				emitBuffer();
				emitToken(word, tokens.T_END_HEREDOC);
				i += word.length - 1;
				continue;
			} else if ((bufferType === "heredoc" || bufferType === "doubleQuotes")) {
				if (lastToken === "[") {
					//Literal array index inside a heredoc or a double quoted string
					emitToken(word, tokens.T_STRING, true);
					i += word.length - 1;
					continue;
				} else if (lastToken === tokens.T_OBJECT_OPERATOR) {
					//Syntax $obj->prop inside strings and heredoc
					emitToken(word, tokens.T_STRING, true);
					i += word.length - 1;
					continue;
				}
			} else if (complexVarPrevBuffer && lastToken === tokens.T_DOLLAR_OPEN_CURLY_BRACES) {
				//Complex variable syntax  ${varname}
				emitToken(word, tokens.T_STRING_VARNAME);
				i += word.length - 1;
				continue;
			}
			emitToken(word, tokens.T_STRING);
			i += word.length - 1;
		} else if (ASCII < 32) {
			//If below ASCII 32 it's a bad character
			emitToken(ch, tokens.T_BAD_CHARACTER);
		} else {
			//If there isn't an open buffer there should be an syntax error, but we don't care
			//so it will be emitted as a simple string
			emitToken(ch, tokens.T_STRING);
		}
	}
	//If there's an open buffer emit it
	if (bufferType && (bufferType !== "doubleQuotes" || !split)) {
		emitBuffer();
	} else {
		splitString();
	}
	return ret;
}
