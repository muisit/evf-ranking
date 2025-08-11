import React from 'react';
import ReactDOM from 'react-dom/client';

import IndexPage from '../components/bo_ranking/index.jsx';

document.addEventListener('DOMContentLoaded', function () {

    const el = document.getElementById('evfranking-root');
    ReactDOM.createRoot(el).render((<IndexPage />));
    
});
