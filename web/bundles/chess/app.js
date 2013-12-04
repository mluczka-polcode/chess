'use strict';

var chessApp = angular.module('chessApp', []);

chessApp.controller('chessboard', function($scope) {
    $scope.tiles = [
        ['r', 'k', 'b', 'q', 'x', 'b', 'k', 'r'],
        ['p', 'p', 'p', 'p', 'p', 'p', 'p', 'p'],
        [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],
        [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],
        [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],
        [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],
        ['P', 'P', 'P', 'P', 'P', 'P', 'P', 'P'],
        ['R', 'K', 'B', 'Q', 'X', 'B', 'K', 'R']
    ];

    $scope.selectedField = {
        x : 0,
        y : 0
    };

    $scope.checkField = function(x, y) {
        $scope.selectedField.x = x;
        $scope.selectedField.y = y;
        var cell = $scope.getCellByCoords(x, y);
        cell.className = 'selected';
    };

    $scope.uncheckField = function() {
        $scope.selectedField.x = 0;
        $scope.selectedField.y = 0;
        var cells = document.getElementById('chessboard').getElementsByTagName('td');
        for(var i = 0; i < cells.length; i++)
        {
            cells[i].className = '';
        }
    };

    $scope.getCellByCoords = function(x, y) {
        var cells = document.getElementById('chessboard').getElementsByTagName('td');
        return cells[x + (8 * y)];
    };

    $scope.onfieldclick = function(x, y) {
        if($scope.selectedField.x == 0)
        {
            if($scope.tiles[y][x] != ' ')
            {
                $scope.checkField(x, y);
            }
        }
        else
        {
            $scope.tiles[y][x] = $scope.tiles[$scope.selectedField.y][$scope.selectedField.x];
            $scope.tiles[$scope.selectedField.y][$scope.selectedField.x] = ' ';
            $scope.uncheckField();
        }
    };
});
