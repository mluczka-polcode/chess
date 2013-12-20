'use strict';

var ChessGame = function(gameState) {
    var self = this;

    var BOARD_SIZE = 8;
    var CHECK_GAMESTATE_TIMEOUT = 2000;

    var checkStateTimer = null;

    var selectedTile = {
        x : -1,
        y : -1
    };

    var possibleMoves = [];

    var checkStateTimer = null;

    self.state = gameState;

    self.$http = null;

    self.showAdvanceDialog = false;
    self.moveToX = null;
    self.moveToY = null;

    self.init = function() {
        clearTimeout(checkStateTimer);
        if(self.state.status == 'in_progress')
        {
            checkStateTimer = setTimeout(checkGameState, CHECK_GAMESTATE_TIMEOUT);
        }
    };

    self.onfieldclick = function(x, y) {
        self.closeAdvanceDialog();

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

    self.getStatusInfo = function() {
        if(self.state.status == 'in_progress')
        {
            if(self.state.currentPlayer == self.state.color)
            {
                return '<b>YOUR TURN</b>';
            }
            else
            {
                return 'waiting for opponent move...';
            }
        }

        if(self.state.status == 'tie')
        {
            return 'Game result: <b>TIE</b>';
        }

        if(self.state.status == self.state.color + '_won')
        {
            return 'Game result: <b class="positive">YOU WON</b>';
        }

        if(self.state.status == getOpponent(self.state.color) + '_won')
        {
            return 'Game result: <b class="negative">YOU LOST</b>';
        }

        return '<span class="negative">invalid status: "' + self.state.status + '"</span>';
    };

    self.canProposeTie = function() {
        if(self.state.status != 'in_progress' || self.state.tieProposal.indexOf('proposed') > 0)
        {
            return false;
        }

        return true;
    };

    self.canAcceptTie = function() {
        if(self.state.status != 'in_progress')
        {
            return false;
        }

        return self.state.tieProposal == getOpponent(self.state.color) + ' proposed';
    };

    self.canCancelTie = function() {
        if(self.state.status != 'in_progress')
        {
            return false;
        }

        return self.state.tieProposal == self.state.color + ' proposed';
    };

    self.proposeTie = function() {
        if(self.state.status != 'in_progress' || self.state.tieProposal == self.state.color + ' proposed')
        {
            return;
        }

        if(!confirm('Are you sure?'))
        {
            return;
        }

        updateTieProposal('propose');
    };

    self.answerTieProposal = function(accept) {
        if(!confirm('Are you sure?'))
        {
            return;
        }

        var message = accept ? 'accept' : 'reject';
        updateTieProposal(message);
    };

    self.cancelTieProposal = function() {
        if(!confirm('Are you sure?'))
        {
            return;
        }

        updateTieProposal('cancel');
    };

    self.opponentRejectedTie = function() {
        return self.state.tieProposal == self.state.color + ' rejected';
    };

    self.surrender = function() {
        if(!confirm('Are you sure?'))
        {
            return;
        }

        self.$http({
            'method': 'POST',
            'url': ajaxUrl + 'surrender/' + self.state.tableId + '/' + self.state.color,
            'headers': {'Content-Type': 'application/x-www-form-urlencoded'}
        }).
        error(function(data, status, headers, config) {
            alert(data);
        });
    };

    self.closeAdvanceDialog = function() {
        self.showAdvanceDialog = false;
    };

    self.advancePawnTo = function(tile) {
        self.closeAdvanceDialog();
        moveSelectedTile(self.moveToX, self.moveToY, tile);
    };

    var updateTieProposal = function(message) {
        self.$http({
            'method': 'POST',
            'url': ajaxUrl + 'tieProposal/' + self.state.tableId + '/' + self.state.color,
            'data': 'message=' + message,
            'headers': {'Content-Type': 'application/x-www-form-urlencoded'}
        }).
        error(function(data, status, headers, config) {
            alert(data);
        });
    };

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

    var moveSelectedTile = function(x, y, advancePawnTo) {
        var tile = self.state.position[selectedTile.y][selectedTile.x].toLowerCase();
        if(tile == 'p' && y == 7 && !advancePawnTo)
        {
            openAdvanceDialog(x, y);
            return;
        }

        var postData = 'fromX=' + selectedTile.x + '&fromY=' + selectedTile.y + '&toX=' + x + '&toY=' + y + '&advancePawnTo=' + advancePawnTo;

        self.$http({
            'method': 'POST',
            'url': ajaxUrl + 'moveTile/' + self.state.tableId,
            'data': postData,
            'headers': {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function(data, status, headers, config) {
            clearTimeout(checkStateTimer);
            unselectTile();
            self.state = data;
            checkStateTimer = setTimeout(checkGameState, CHECK_GAMESTATE_TIMEOUT);
        }).
        error(function(data, status, headers, config) {
            alert(data);
        });
    };

    var openAdvanceDialog = function(x, y) {
        self.showAdvanceDialog = true;
        self.moveToX = x;
        self.moveToY = y;
    };

    var getOpponent = function(player) {
        return player == 'black' ? 'white' : 'black';
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
