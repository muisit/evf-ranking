import React from 'react';
import ReactDOM from 'react-dom/client';

import IndexPage from '../components/bo_ranking/index.jsx';

document.addEventListener('DOMContentLoaded', function () {

    ReactDOM.createRoot(document.getElementById('evfranking-root')).render((<IndexPage />));
    
});
