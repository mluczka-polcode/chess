'use strict';

var chessApp = angular.module('chessApp', []);

chessApp.filter('reverse', function() {
    return function(items) {
        return items.slice().reverse();
    };
});

chessApp.controller('chessboard', function($scope, $http) {
    var BOARD_SIZE = 8;

    $scope.tiles = chessboardTiles;

    $scope.onfieldclick = function(x, y) {
//         console.info(x + ', ' + y);
        if(hasSelectedTile() && isMovePossible(x, y))
        {
            moveSelectedTile(x, y);
        }
        else if(isMyTile(x, y) && tileCanMove(x, y))
        {
            selectTile(x, y);
        }
        else
        {
            unselectTile();
        }
    };

    var selectedTile = {
        x : -1,
        y : -1
    };

    var possibleMoves = [];

    var getPossibleMoves = function(x, y) {
        var functions = {
            'p': getPawnMoves,
            'k': getKnightMoves,
            'b': getBishopMoves,
            'r': getRookMoves,
            'q': getQueenMoves,
            'x': getKingMoves
        };
        var tile = $scope.tiles[y][x].toLowerCase();
        if(typeof(functions[tile]) != 'function')
        {
            return false;
        }
        return functions[tile](x, y);
    };

    var getPawnMoves = function(x, y) {
        var result = [];
        var modifier = currentPlayer == 'white' ? 1 : -1;

        // move
        if(isEmptyField(x, y + modifier))
        {
            result.push([x, y + modifier]);
            if((currentPlayer == 'white' && y == 1) && isEmptyField(x, 3))
            {
                result.push([x, 3]);
            }
            else if((currentPlayer == 'black' && y == 6) && isEmptyField(x, 4))
            {
                result.push([x, 4]);
            }
        }

        // beat
        if(isEnemy(x - 1, y + modifier))
        {
            result.push([x - 1, y + modifier]);
        }
        if(isEnemy(x + 1, y + modifier))
        {
            result.push([x + 1, y + modifier]);
        }

        // TODO: bicie w przelocie

        return result;
    };

    var getKnightMoves = function(x, y) {
        var result = [];

        var allowedMoves = [
            {'x':  1, 'y':  2},
            {'x':  2, 'y':  1},
            {'x':  2, 'y': -1},
            {'x':  1, 'y': -2},
            {'x': -1, 'y': -2},
            {'x': -2, 'y': -1},
            {'x': -2, 'y':  1},
            {'x': -1, 'y':  2}
        ];

        var toX, toY;
        for(var i = 0; i < allowedMoves.length; i++)
        {
            toX = x + allowedMoves[i].x;
            toY = y + allowedMoves[i].y;
            if(canMoveOrBeat(toX, toY))
            {
                result.push([toX, toY]);
            }
        }

        return result;
    };

    var getBishopMoves = function(x, y) {
        return getPossibleLongMoves(x, y, [
            {'x': 1,  'y':  1},
            {'x': 1,  'y': -1},
            {'x': -1, 'y':  1},
            {'x': -1, 'y': -1}
        ]);
    };

    var getRookMoves = function(x, y) {
        return getPossibleLongMoves(x, y, [
            {'x':  1,  'y':  0},
            {'x': -1,  'y': 0},
            {'x':  0, 'y':  1},
            {'x':  0, 'y': -1}
        ]);
    };

    var getQueenMoves = function(x, y) {
        return getPossibleLongMoves(x, y, [
            {'x':  1, 'y':  1},
            {'x':  1, 'y':  0},
            {'x':  1, 'y': -1},
            {'x':  0, 'y': -1},
            {'x': -1, 'y': -1},
            {'x': -1, 'y':  0},
            {'x': -1, 'y':  1},
            {'x':  0, 'y':  1}
        ]);
    };

    var getKingMoves = function(x, y) {
        var result = [];

        for(var i = -1; i <= 1; i++)
        {
            for(var j = -1; j <= 1; j++)
            {
                toX = x + i;
                toY = y + j;
                if(canMoveOrBeat(toX, toY) && !isAttacked(toX, toY))
                {
                    result.push([toX, toY]);
                }
            }
        }

        return result;
    };

    var getPossibleLongMoves = function(x, y, directions) {
        var result = [];
        for(var i = 0; i < directions.length; i++)
        {
            var j = 1;
            var toX = x + (j * directions[i].x);
            var toY = y + (j * directions[i].y);
            while(isEmptyField(toX, toY))
            {
                result.push([toX, toY]);
                j += 1;
                toX = x + (j * directions[i].x);
                toY = y + (j * directions[i].y);
            }
            if(isEnemy(toX, toY))
            {
                result.push([toX, toY]);
            }
        }
        return result;
    };

    var validCoords = function(x, y) {
        if(x < 0 || x >= BOARD_SIZE || y < 0 || y >= BOARD_SIZE)
        {
            return false;
        }
        return true;
    };

    var isEmptyField = function(x, y) {
        if(!validCoords(x, y))
        {
            return false;
        }
        return($scope.tiles[y][x] == ' ' ? true : false);
    };

    var isEnemy = function(x, y) {
        return(validCoords(x, y) && !isEmptyField(x, y) && !isMyTile(x, y));
    };

    var canMoveOrBeat = function(x, y) {
        return(validCoords(x, y) && (isEmptyField(x, y) || isEnemy(x, y)));
    };

    var isAttacked = function(x, y) {
        //...
        return false;
    };

    var selectTile = function(x, y) {
        possibleMoves = getPossibleMoves(x, y);

        clearSelection();

        selectedTile.x = x;
        selectedTile.y = y;
        var cell = getCellByCoords(x, y);
        cell.className = 'selected';

        for(var i = 0; i < possibleMoves.length; i++)
        {
            cell = getCellByCoords(possibleMoves[i][0], possibleMoves[i][1]);
            cell.className = 'avail';
        }
    };

    var unselectTile = function() {
        selectedTile.x = -1;
        selectedTile.y = -1;
        possibleMoves = [];
        clearSelection();
    };

    var clearSelection = function() {
        var cells = document.getElementById('chessboard').getElementsByTagName('td');
        for(var i = 0; i < cells.length; i++)
        {
            cells[i].className = '';
        }
    };

    var hasSelectedTile = function() {
        return(selectedTile.x == -1 ? false : true);
    };

    var isMovePossible = function(x, y) {
        for(var i = 0; i < possibleMoves.length; i++)
        {
            if(possibleMoves[i][0] == x && possibleMoves[i][1] == y)
            {
                return true;
            }
        }
        return false;
    };

    var isMyTile = function(x, y) {
        if(!validCoords(x, y))
        {
            return false;
        }

        var tile = $scope.tiles[y][x];
        if(tile == ' ')
        {
            return false;
        }

        if(currentPlayer == 'white' && tile.toLowerCase() === tile)
        {
            return false;
        }

        if(currentPlayer == 'black' && tile.toUpperCase() === tile)
        {
            return false;
        }

        return true;
    };

    var tileCanMove = function(x, y) {
        var possibleMoves = getPossibleMoves(x, y);
        return(possibleMoves && possibleMoves.length ? true : false);
    };

    var getCellByCoords = function(x, y) {
        var cells = document.getElementById('chessboard').getElementsByTagName('td');
        y = BOARD_SIZE - (y + 1);
        return cells[x + (BOARD_SIZE * y)];
    };

    var moveSelectedTile = function(x, y) {
        var url = ajaxUrl + 'moveTile/' + tableId + '/' + selectedTile.x + ',' + selectedTile.y + '-' + x + ',' + y;
        $http({'method': 'GET', 'url': url}).
            success(function(data, status, headers, config) {
                if(data != 'ok')
                {
                    alert(data);
                    return;
                }
                $scope.tiles[y][x] = $scope.tiles[selectedTile.y][selectedTile.x];
                $scope.tiles[selectedTile.y][selectedTile.x] = ' ';
                unselectTile();
            }).
            error(function(data, status, headers, config) {
                alert(data);
            });
    };

});
