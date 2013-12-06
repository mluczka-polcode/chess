'use strict';

var chessApp = angular.module('chessApp', []);

chessApp.filter('reverse', function() {
    return function(items) {
        return items.slice().reverse();
    };
});

chessApp.factory('game', function() {
    return new ChessGame(chessGamestate);
});

chessApp.controller('chessboard', function($scope, $http, game) {
    $scope.game = game;
    $scope.game.$http = $http;
    setTimeout($scope.game.init, 100);
});
