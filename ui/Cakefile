###
Cakefile - Task automation for Capsule development

Copyright (C) 2013 Crowd Favorite, Ltd. All rights reserved.

This file is part of Capsule.

Capsule is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of the License, or (at your
option) any later version.

Capsule is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
02110-1301, USA.
###

requirejs = require 'requirejs'
{minify} = require 'uglify-js'
fs = require 'fs'
temp = require 'temp'

licensePreamble = """/*
Copyright (C) 2013 Crowd Favorite, Ltd. All rights reserved.

This file is part of Capsule.

Capsule is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of the License, or (at your
option) any later version.

Capsule is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
02110-1301, USA.
*/
"""

buildfile = "./rjs-built.js"

rjsBuilt = temp.openSync()

appJS = [
  'lib/require.js',
  'assets/js/requirejs_config.js',
  'lib/phpjs/functions/datetime/date.js',
  'lib/twitter-text-js/twitter-text.js',
  'lib/json-js/json2.js',
  'lib/jquery-scrollintoview/jquery.scrollintoview.min.js',
  'lib/sidr/dist/jquery.sidr.js',
  'lib/linkify/1.0/jquery.linkify-1.0-min.js',
  'lib/jquery.hotkeys/jquery.hotkeys.js',
  rjsBuilt.path
  'assets/js/load.js',
]

noUglifyJS = [
  rjsBuilt.path,
  'lib/jquery-scrollintoview/jquery.scrollintoview.min.js',
  'lib/linkify/1.0/jquery.linkify-1.0-min.js'
]

uglifyOptions = {}

buildAppJS = (options, output_dir="./public/javascripts") ->
  console.log "running r.js optimizer"
  requireConfig =
    nodeRequire: require
    baseUrl: '.'
    paths:
      cf: './assets'
      ace: './lib/ace/lib/ace'
      jquery: 'empty:'
    optimize: 'uglify2'
    out: rjsBuilt.path
    inlineText: true
    include: ['jquery',
                'ace/ace',
                'ace/mode/text', 'ace/lib/dom', 'ace/tokenizer',
                'ace/theme/textmate', 'ace/theme/twilight',
                'cf/js/capsule',
                'cf/js/syntax/cf_php_highlight_rules', 'cf/js/syntax/cfmarkdown',
                'cf/js/static_highlight',
                'ace/requirejs/text!ace/theme/textmate.css'
              ]


  requirejs.optimize requireConfig, (buildResponse) ->
    console.log "done"
    concatAppJS()
  , (error) ->
    console.log(error)

concatAppJS = (outfile = './assets/js/optimized.js') ->
  console.log "building to #{outfile}"
  fs.writeFileSync(outfile, licensePreamble + '\n')
  for jsfile in appJS
    stats = fs.statSync(jsfile)
    if jsfile is rjsBuilt.path
      fs.appendFileSync(outfile, "\n/*** r.js built ***/\n")
    else
      fs.appendFileSync(outfile, "\n/*** #{jsfile} ***/\n")
    if jsfile in noUglifyJS
      console.log "concatenating #{jsfile} - #{stats.size} bytes"
      fs.appendFileSync(outfile, fs.readFileSync(jsfile))
    else
      min = minify(jsfile, uglifyOptions).code
      console.log "minifying #{jsfile} - #{stats.size} bytes became #{min.length} bytes"
      fs.appendFileSync(outfile, min)
      min = null
  console.log "done building to #{outfile}"


task 'build', 'compile javascript', ->
  buildAppJS()
