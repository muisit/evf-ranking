import React from 'react';
import ReactDOM from 'react-dom';
import RankingPage from '../components/ranking/index';

document.addEventListener('DOMContentLoaded', function () {
    ReactDOM.render(<RankingPage />, document.getElementById('evfranking-ranking'));
});
