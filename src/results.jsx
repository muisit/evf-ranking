import React from 'react';
import ReactDOM from 'react-dom/client';

import ResultsPage from '../components/results/index.jsx';

document.addEventListener('DOMContentLoaded', function () {

    ReactDOM.createRoot(document.getElementById('evfranking-results')).render(<ResultsPage />);
    
});
