'use strict';

var ChessGame = function(config) { //chessboardTiles, chessboardLog, currentPlayer, playerColor) {
    var self = this;

    var BOARD_SIZE = 8;
    var CHECK_GAMESTATE_TIMEOUT = 2000;

    var checkStateTimer = null;

    self.tiles = config.chessboardTiles;
    self.log = config.chessboardLog;
    self.currentPlayer = config.currentPlayer;
    self.playerColor = config.playerColor;
    self.ajaxUrl = config.ajaxUrl;
    self.tableId = config.tableId;
    self.lastMove = config.lastMove;

    self.$http = null;

    self.init = function() {
        updateSelection();
        clearTimeout(checkStateTimer);
        checkStateTimer = setTimeout(checkGameState, CHECK_GAMESTATE_TIMEOUT);
    };

    self.onfieldclick = function(x, y) {
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

    var knightMoves = [
        {'x':  1, 'y':  2},
        {'x':  2, 'y':  1},
        {'x':  2, 'y': -1},
        {'x':  1, 'y': -2},
        {'x': -1, 'y': -2},
        {'x': -2, 'y': -1},
        {'x': -2, 'y':  1},
        {'x': -1, 'y':  2}
    ];

    var diagonalMoves = [
        {'x': 1,  'y':  1},
        {'x': 1,  'y': -1},
        {'x': -1, 'y':  1},
        {'x': -1, 'y': -1}
    ];

    var straightMoves = [
        {'x':  1, 'y':  0},
        {'x': -1, 'y':  0},
        {'x':  0, 'y':  1},
        {'x':  0, 'y': -1}
    ]

    var checkStateTimer = null;

    var updateSelection = function() {
        var cells = document.getElementById('chessboard').getElementsByTagName('td');
        for(var i = 0; i < cells.length; i++)
        {
            removeClass(cells[i], 'moved');
            removeClass(cells[i], 'checked');
        }

        if(self.lastMove)
        {
            var cellFrom = getCellByCoords(self.lastMove.fromX, self.lastMove.fromY);
            addClass(cellFrom, 'moved');

            var cellTo = getCellByCoords(self.lastMove.toX, self.lastMove.toY);
            addClass(cellTo, 'moved');
        }

        for(var y = 0; y < self.tiles.length; y++)
        {
            for(var x = 0; x < self.tiles[y].length; x++)
            {
                if(isMyTile(x, y) && isKing(x, y) && isAttacked(x, y))
                {
                    addClass(getCellByCoords(x, y), 'checked')
                    return;
                }
            }
        }
    };

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

        knightMoves.forEach(function(move){
            var toX = x + move.x;
            var toY = y + move.y;
            if(canMoveOrBeat(toX, toY))
            {
                result.push([toX, toY]);
            }
        });

        return result;
    };

    var getBishopMoves = function(x, y) {
        return getLongMoves(x, y, diagonalMoves);
    };

    var getRookMoves = function(x, y) {
        return getLongMoves(x, y, straightMoves);
    };

    var getQueenMoves = function(x, y) {
        return getLongMoves(x, y, straightMoves.concat(diagonalMoves));
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

    var getLongMoves = function(x, y, directions) {
        var result = [];

        directions.forEach(function(move) {
            var j = 1;
            var toX = x + (j * move.x);
            var toY = y + (j * move.y);
            while(isEmptyField(toX, toY))
            {
                result.push([toX, toY]);
                j += 1;
                toX = x + (j * move.x);
                toY = y + (j * move.y);
            }
            if(isEnemy(toX, toY))
            {
                result.push([toX, toY]);
            }
        });
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

        // pawn
        if(isEnemy(x - 1, y + 1) && isPawn(x - 1, y + 1))
        {
            return true;
        }
        if(isEnemy(x + 1, y + 1) && isPawn(x + 1, y + 1))
        {
            return true;
        }

        // knight
        for(var i = 0; i < knightMoves.length; i++)
        {
            var toX = x + knightMoves[i].x;
            var toY = y + knightMoves[i].y;
            if(isEnemy(toX, toY) && isKnight(toX, toY))
            {
                return true;
            }
        }

        // bishop or queen
        for(var i = 0; i < diagonalMoves.length; i++)
        {
            var j = 1;
            var toX = x + (j * diagonalMoves[i].x);
            var toY = y + (j * diagonalMoves[i].y);
            while(isEmptyField(toX, toY))
            {
                j += 1;
                toX = x + (j * diagonalMoves[i].x);
                toY = y + (j * diagonalMoves[i].y);
            }
            if(isEnemy(toX, toY) && (isBishop(toX, toY) || isQueen(toX, toY)))
            {
                return true;
            }
        }

        // rook or queen
        for(var i = 0; i < straightMoves.length; i++)
        {
            var j = 1;
            var toX = x + (j * straightMoves[i].x);
            var toY = y + (j * straightMoves[i].y);
            while(isEmptyField(toX, toY))
            {
                j += 1;
                toX = x + (j * straightMoves[i].x);
                toY = y + (j * straightMoves[i].y);
            }
            if(isEnemy(toX, toY) && (isRook(toX, toY) || isQueen(toX, toY)))
            {
                return true;
            }
        }

        // king
        for(var i = -1; i <= 1; i++)
        {
            for(var j = -1; j <= 1; j++)
            {
                var toX = x + i;
                var toY = y + j;
                if(isEnemy(toX, toY) && isKing(toX, toY))
                {
                    return true;
                }
            }
        }

        return false;
    };

    var isPawn = function(x, y) {
        return(validCoords(x, y) && self.tiles[y][x].toLowerCase() == 'p');
    };

    var isKnight = function(x, y) {
        return(validCoords(x, y) && self.tiles[y][x].toLowerCase() == 'k');
    };

    var isBishop = function(x, y) {
        return(validCoords(x, y) && self.tiles[y][x].toLowerCase() == 'b');
    };

    var isRook = function(x, y) {
        return(validCoords(x, y) && self.tiles[y][x].toLowerCase() == 'r');
    };

    var isQueen = function(x, y) {
        return(validCoords(x, y) && self.tiles[y][x].toLowerCase() == 'q');
    };

    var isKing = function(x, y) {
        return(validCoords(x, y) && self.tiles[y][x].toLowerCase() == 'x');
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
        addClass(cell, 'selected');

        possibleMoves.forEach(function(move) {
            cell = getCellByCoords(move[0], move[1]);
            addClass(cell, 'avail');
        });
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
        updateSelection();
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
        var url = self.ajaxUrl + 'moveTile/' + self.tableId + '/' + selectedTile.x + ',' + selectedTile.y + '-' + x + ',' + y;
        self.$http({'method': 'GET', 'url': url}).
            success(function(data, status, headers, config) {
                if(data != 'ok')
                {
                    alert(data);
                    return;
                }
                clearTimeout(checkStateTimer);
                unselectTile();
                switchPlayer();
                checkGameState();
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

    var checkGameState = function() {
//         console.info('checking gamestate: ' + self.playerColor);
        var url = self.ajaxUrl + 'checkGameState/' + self.tableId + '/' + self.playerColor;
        self.$http({'method': 'GET', 'url': url}).
            success(function(data, status, headers, config) {
                if(data.currentPlayer != self.currentPlayer)
                {
                    self.currentPlayer = data.currentPlayer;
                }
                self.tiles = data.position;
                self.log = data.log;
                self.lastMove = data.lastMove;
                updateSelection();
            }).
            error(function(data, status, headers, config) {
                alert(data);
            });

        clearTimeout(checkStateTimer);
        checkStateTimer = setTimeout(checkGameState, CHECK_GAMESTATE_TIMEOUT);
    };

    var addClass = function(element, className) {
        if(element.className.indexOf(className) === -1)
        {
            element.className += ' ' + className;
        }
    };

    var removeClass = function(element, className) {
        element.className = element.className.replace(className, '');
    };
};
