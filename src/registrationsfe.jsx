import React from 'react';
import ReactDOM from 'react-dom';
import version from './version';

import RegistrationPage from '../components/registration/index.jsx';

document.addEventListener('DOMContentLoaded', function () {

    ReactDOM.render((<RegistrationPage version={version}/>), document.getElementById('evfregistration-frontend-root'));
    
});
