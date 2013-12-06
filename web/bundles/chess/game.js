'use strict';

var ChessGame = function(chessboardTiles, chessboardLog, currentPlayer, playerColor) {
    var self = this;

    var BOARD_SIZE = 8;
    var CHECK_GAMESTATE_TIMEOUT = 2000;

    this.tiles = chessboardTiles;
    this.log = chessboardLog;
    this.currentPlayer = currentPlayer;
    this.playerColor = playerColor;
    this.$http = null;

    this.onfieldclick = function(x, y) {
        if(self.currentPlayer != self.playerColor)
        {
            return false;
        }

        if(hasSelectedTile() && isMovePossible(x, y))
        {
            moveSelectedTile(x, y);
        }
        else if(isMyTile(x, y) && tileCanMove(x, y) && (x != selectedTile.x || y != selectedTile.y))
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

    var checkStateTimer = null;

    var getPossibleMoves = function(x, y) {
        var functions = {
            'p': getPawnMoves,
            'k': getKnightMoves,
            'b': getBishopMoves,
            'r': getRookMoves,
            'q': getQueenMoves,
            'x': getKingMoves
        };
        var tile = self.tiles[y][x].toLowerCase();
        if(typeof(functions[tile]) != 'function')
        {
            return false;
        }
        return functions[tile](x, y);
    };

    var getPawnMoves = function(x, y, mode) {
        if(!mode)
        {
            mode = 'all';
        }

        var result = [];

        if(mode == 'move' || mode == 'all')
        {
            if(isEmptyField(x, y + 1))
            {
                result.push([x, y + 1]);

                if(y == 1 && isEmptyField(x, y + 2))
                {
                    result.push([x, y + 2]);
                }
            }
        }

        if(mode == 'beat' || mode == 'all')
        {
            if(isEnemy(x - 1, y + 1))
            {
                result.push([x - 1, y + 1]);
            }
            if(isEnemy(x + 1, y + 1))
            {
                result.push([x + 1, y + 1]);
            }

            // TODO: bicie w przelocie
            // ...
        }

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

        var toX, toY;
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
        return(self.tiles[y][x] == '_' ? true : false);
    };

    var isEnemy = function(x, y) {
        return(validCoords(x, y) && !isEmptyField(x, y) && !isMyTile(x, y));
    };

    var canMoveOrBeat = function(x, y) {
        return(validCoords(x, y) && (isEmptyField(x, y) || isEnemy(x, y)));
    };

    var isAttacked = function(x, y) {
        // TODO: implement
        return false;
    };

    var canBeat = function(tile, x, y) {
        var possibleMoves = getPossibleMoves(tile.x, tile.y, 'beat');
        for(var i = 0; i < possibleMoves.length; i++)
        {
            if(possibleMoves[i].x == x && possibleMoves[i].y == y)
            {
                return true;
            }
        }
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

        var tile = self.tiles[y][x];
        if(tile == '_')
        {
            return false;
        }

        if(self.playerColor == 'white' && tile.toLowerCase() === tile)
        {
            return false;
        }

        if(self.playerColor == 'black' && tile.toUpperCase() === tile)
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
        self.$http({'method': 'GET', 'url': url}).
            success(function(data, status, headers, config) {
                if(data != 'ok')
                {
                    alert(data);
                    return;
                }
                self.tiles[y][x] = self.tiles[selectedTile.y][selectedTile.x];
                self.tiles[selectedTile.y][selectedTile.x] = '_';
                unselectTile();
                switchPlayer();
            }).
            error(function(data, status, headers, config) {
                alert(data);
            });
    };

    var switchPlayer = function() {
        if(self.currentPlayer == 'black')
        {
            self.currentPlayer = 'white';
        }
        else
        {
            self.currentPlayer = 'black';
        }
    };

    var checkIfOpponentMoved = function() {
//         console.info('checking gamestate: ' + self.playerColor);
        var url = ajaxUrl + 'checkGameState/' + tableId + '/' + self.playerColor;
        self.$http({'method': 'GET', 'url': url}).
            success(function(data, status, headers, config) {
                if(data.currentPlayer != self.currentPlayer)
                {
                    switchPlayer();
                }
                self.tiles = data.position;
                self.log = data.log;
            }).
            error(function(data, status, headers, config) {
                alert(data);
            });

        clearTimeout(checkStateTimer);
        checkStateTimer = setTimeout(checkIfOpponentMoved, CHECK_GAMESTATE_TIMEOUT);
    };

    clearTimeout(checkStateTimer);
    checkStateTimer = setTimeout(checkIfOpponentMoved, CHECK_GAMESTATE_TIMEOUT);
};
