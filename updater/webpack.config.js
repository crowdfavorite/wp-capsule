const path = require("path");
const webpack = require("webpack");

const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  mode: process.env.NODE_ENV,
  entry: {
    admin: ['./assets/js/admin.js','./assets/scss/admin.scss'],
  },
  output: {
    filename: '[name].js',
  },
  module: {
    rules: [
		{
			test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
			use: [
			  {
				loader: 'file-loader',
				options: {
				  name: '[name].[ext]',
				  outputPath: 'fonts/',
				  esModule: false,
				}
			  }
			]
		  },
		  {
			test: /\.scss$/,
            use: [{
                loader: MiniCssExtractPlugin.loader
            },
                {
                    loader: "css-loader",
                    options: {
                        sourceMap: true
                    }
                },
				"sass-loader"
			],
		  },
	  {
		test: /\.(js|jsx)$/,
		exclude: /(node_modules|bower_components)/,
		loader: "babel-loader",
		options: { presets: ["@babel/env"] }
	  },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
  ],
};