'use strict';

var ChessGame = function(gameState) {
    var self = this;

    var BOARD_SIZE = 8;
    var CHECK_GAMESTATE_TIMEOUT = 2000;

    var checkStateTimer = null;

    self.state = gameState;

    self.$http = null;

    self.init = function() {
        clearTimeout(checkStateTimer);
        if(self.state.status == 'in_progress')
        {
            checkStateTimer = setTimeout(checkGameState, CHECK_GAMESTATE_TIMEOUT);
        }
    };

    self.onfieldclick = function(x, y) {
        if(self.state.currentPlayer != self.state.color || self.state.status != 'in_progress')
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

    self.getFieldClass = function(x, y) {
        var classes = [];

        if(self.state.lastMove && x == self.state.lastMove.fromX && y == self.state.lastMove.fromY)
        {
            classes.push('moved');
        }
        else if(self.state.lastMove && x == self.state.lastMove.toX && y == self.state.lastMove.toY)
        {
            classes.push('moved');
        }

        if(isKing(x, y) && isCurrentPlayerTile(x, y) && self.state.kingAttacked)
        {
            classes.push('checked');
        }

        if(x == selectedTile.x && y == selectedTile.y)
        {
            classes.push('selected');
        }
        else if(possibleMoves)
        {
            possibleMoves.forEach(function(move) {
                if(x == move.x && y == move.y)
                {
                    classes.push('avail');
                }
            });
        }

        return classes.join(' ');
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

    var getPossibleMoves = function(x, y) {
        if(!self.state.possibleMoves[x])
        {
            return [];
        }
        return self.state.possibleMoves[x][y];
    };

    var validCoords = function(x, y) {
        if(x < 0 || x >= BOARD_SIZE || y < 0 || y >= BOARD_SIZE)
        {
            return false;
        }
        return true;
    };

    var selectTile = function(x, y) {
        selectedTile.x = x;
        selectedTile.y = y;
        possibleMoves = getPossibleMoves(x, y);
    };

    var unselectTile = function() {
        selectedTile.x = -1;
        selectedTile.y = -1;
        possibleMoves = [];
    };

    var hasSelectedTile = function() {
        return(selectedTile.x == -1 ? false : true);
    };

    var isMovePossible = function(x, y) {
        for(var i = 0; i < possibleMoves.length; i++)
        {
            if(possibleMoves[i].x == x && possibleMoves[i].y == y)
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

        var tile = self.state.position[y][x];
        if(tile == '_')
        {
            return false;
        }

        if(self.state.color == 'white' && tile.toLowerCase() === tile)
        {
            return false;
        }

        if(self.state.color == 'black' && tile.toUpperCase() === tile)
        {
            return false;
        }

        return true;
    };

    var isKing = function(x, y) {
        return ( self.state.position[y][x].toLowerCase() == 'x');
    };

    var isCurrentPlayerTile = function(x, y) {
        var tile = self.state.position[y][x];
        if(self.state.currentPlayer == 'white' && tile.toUpperCase() === tile)
        {
            return true;
        }

        if(self.state.currentPlayer == 'black' && tile.toLowerCase() === tile)
        {
            return true;
        }

        return false;
    };

    var tileCanMove = function(x, y) {
        var possibleMoves = getPossibleMoves(x, y);
        return ( possibleMoves && possibleMoves.length ? true : false );
    };

    var getCellByCoords = function(x, y) {
        var cells = document.getElementById('chessboard').getElementsByTagName('td');
        y = BOARD_SIZE - (y + 1);
        return cells[x + (BOARD_SIZE * y)];
    };

    var moveSelectedTile = function(x, y) {
        var url = ajaxUrl + 'moveTile/' + self.state.tableId + '/' + selectedTile.x + ',' + selectedTile.y + '-' + x + ',' + y;
        self.$http({'method': 'GET', 'url': url}).
            success(function(data, status, headers, config) {
                clearTimeout(checkStateTimer);
                unselectTile();
                self.state = data;
                checkStateTimer = setTimeout(checkGameState, CHECK_GAMESTATE_TIMEOUT);
            }).
            error(function(data, status, headers, config) {
                alert(data);
            });
    };

    var switchPlayer = function() {
        if(self.state.currentPlayer == 'black')
        {
            self.state.currentPlayer = 'white';
        }
        else
        {
            self.state.currentPlayer = 'black';
        }
    };

    var checkGameState = function() {
        if(self.state.status != 'in_progress')
        {
            return false;
        }

        var url = ajaxUrl + 'checkGameState/' + self.state.tableId + '/' + self.state.color;
        self.$http({'method': 'GET', 'url': url}).
            success(function(data, status, headers, config) {
                self.state = data;
            }).
            error(function(data, status, headers, config) {
                alert(data);
            });

        clearTimeout(checkStateTimer);
        checkStateTimer = setTimeout(checkGameState, CHECK_GAMESTATE_TIMEOUT);
    };

    self.init();
};
