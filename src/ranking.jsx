import React from 'react';
import ReactDOM from 'react-dom/client';
import RankingPage from '../components/ranking/index';

document.addEventListener('DOMContentLoaded', function () {
    ReactDOM.createRoot(document.getElementById('evfranking-ranking')).render(<RankingPage />);
});
