const path = require('path')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')

const VENDOR = path.join(__dirname, 'node_modules')
const LOCAL_JS = path.join(__dirname, 'src')
const LOCAL_CSS = path.join(__dirname, 'css')
const BUILD_DIR = path.join(__dirname, 'dist')

module.exports = {
  entry: {
//    vendor: [
//      `${VENDOR}/jquery/dist/jquery.js`,
//    ],
    app: [
      `${LOCAL_JS}/index.jsx`,
      `${LOCAL_CSS}/evfranking.css`
    ],
    ranking: `${LOCAL_JS}/ranking.jsx`,
    results: `${LOCAL_JS}/results.jsx`,
  },
  module:  {
    rules: [
      {
        test: /.jsx?$/,
        exclude: /node_modules/,
        resolve: {
          extensions: ['.jsx','.js']
        },
        use: {
          loader: "babel-loader",
          options: {
            presets: ['@babel/preset-env', '@babel/preset-react'],
            plugins: ["@babel/plugin-transform-class-properties", "@babel/plugin-transform-react-jsx"]
          }
        }
      },
      {
        test: /\.css$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader'
        ],
        resolve: {
          extensions: ['.jsx','.css']
        },
      },
      {    
        test: /\.(woff|woff2|eot|ttf|otf)$/,
        loader: "file-loader",
        options: {
          name: "[name].[ext]",
          outputPath: "fonts/"
        }
      },
      {
        test: /\.(jpe?g|png|gif|svg)$/i, 
        loader: "file-loader",
        options: {
          name: "[name].[ext]",
          outputPath: "images/"
        }
      }
    ],
  },
  devtool: 'source-map',
  resolve: {
    extensions: ['.js', '.jsx','.css', '.scss']
  },
  output: {
    path: BUILD_DIR,
    filename: "[name].js",
//    publicPath: "../wp-content/plugins/evf-ranking/dist/"
  },
  plugins: [
//    new CleanWebpackPlugin(),
    new MiniCssExtractPlugin()
  ]
};