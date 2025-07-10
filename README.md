# evf-ranking
Wordpress plugin to register and peruse results and rankings for the European Veterans Fencing association

### Dependencies

#### Babel
@babel/core
@babel/preset-env -> integration with browserlist
@babel/preset-react -> babel presets for React development
@babel/plugin-transform-class-properties -> transforms class properties
@babel/plugin-transform-react-jsx -> transforms jsx files to js

#### Webpack
webpack
webpack-cli -> to run scripts in package.json
babel-loader -> to load the babel transpiler
css-loader -> unknown
file-loader -> to move fonts and images to specific directories
mini-css-extract-plugin -> to extract styling from JSX and JS into CSS files
style-loader -> unknown
url-loader -> unknown
clean-webpack-plugin -> not used anymore

#### PrimeReact
primereact
primeicons
primeflex

#### React
react
react-contenteditable
react-dom
react-test-renderer
react-transition-group

#### React-drop-and-drag
react-dnd
react-dndn-html5-backend

#### JS testing
jest -> run javascript tests
babel-jest -> babel interface for testing

#### Other
moment -> date and time management
lodash.clonedeep -> cloning objects
classnames -> unknown
cross-env -> run scripts in different environments
