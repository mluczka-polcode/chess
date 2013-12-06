'use strict';

var checkStateTimer = null;

var chessApp = angular.module('chessApp', []);

chessApp.filter('reverse', function() {
    return function(items) {
        return items.slice().reverse();
    };
});

chessApp.factory('game', function() {
    return new ChessGame(chessboardTiles, chessboardLog, currentPlayer, playerColor);
});

chessApp.controller('chessboard', function($scope, $http, game) {
    $scope.game = game;
    $scope.game.$http = $http;
});
