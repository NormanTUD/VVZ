const board = [];
const boardWidth = 26, boardHeight = 16;

var startedSnake = 0;
var numberOfCtrlPresses = 0;
var numberOfUpKeyPresses = 0;
var snakeX;
var snakeY;
var snakeLength;
var snakeDirection;

function initGame() {
	const boardElement = document.getElementById('snakeboard');

	for (var y = 0; y < boardHeight; ++y) {
		var row = [];
		for (var x = 0; x < boardWidth; ++x) {
			var cell = {};

			cell.element = document.createElement('div');

			boardElement.appendChild(cell.element);

			row.push(cell);
		}

		board.push(row);
	}

	startGame();

	gameLoop();
}

function placeApple() {
	var appleX = Math.floor(Math.random() * boardWidth);
	var appleY = Math.floor(Math.random() * boardHeight);

	board[appleY][appleX].apple = 1;
}

function startGame() {
	snakeX = Math.floor(boardWidth / 2);
	snakeY = Math.floor(boardHeight / 2);
	snakeLength = 5;
	snakeDirection = 'Up';

	for (var y = 0; y < boardHeight; ++y) {
		for (var x = 0; x < boardWidth; ++x) {
			board[y][x].snake = 0;
			board[y][x].apple = 0;
		}
	}

	board[snakeY][snakeX].snake = snakeLength;

	placeApple();
}

function gameLoop() {
	switch (snakeDirection) {
		case 'Up':    snakeY--; break;
		case 'Down':  snakeY++; break;
		case 'Left':  snakeX--; break;
		case 'Right': snakeX++; break;
	}

	if (snakeX < 0 || snakeY < 0 || snakeX >= boardWidth || snakeY >= boardHeight) {
		startGame();
	}

	if (board[snakeY][snakeX].snake > 0) {
		startGame();
	}

	if (board[snakeY][snakeX].apple === 1) {
		snakeLength++;
		board[snakeY][snakeX].apple = 0;
		placeApple();
	}

	board[snakeY][snakeX].snake = snakeLength;

	for (var y = 0; y < boardHeight; ++y) {
		for (var x = 0; x < boardWidth; ++x) {
			var cell = board[y][x];

			if (cell.snake > 0) {
				cell.element.className = 'snake';
				cell.snake -= 1;
			}
			else if (cell.apple === 1) {
				cell.element.className = 'apple';
			}
			else {
				cell.element.className = '';
			}
		}
	}

	setTimeout(gameLoop, 1000 / snakeLength);
}

function enterKey(event) {
	switch (event.key) {
		case 'ArrowUp': snakeDirection = 'Up'; break;
		case 'ArrowDown': snakeDirection = 'Down'; break;
		case 'ArrowLeft': snakeDirection = 'Left'; break;
		case 'ArrowRight': snakeDirection = 'Right'; break;
		default: break;
	}
	event.preventDefault();
}

function checkKey(e) {
	if(!startedSnake) {
		e = e || window.event;
		if(e.keyCode == '17' || numberOfCtrlPresses >= 2) {
			numberOfCtrlPresses = numberOfCtrlPresses + 1;
			if (e.keyCode == '38') {
				numberOfUpKeyPresses = numberOfUpKeyPresses + 1;
				if(numberOfUpKeyPresses == 3) {
					console.log("Starting Snake");
					$("body").append("<div id='snakeboardwrapper'><div id='snakeboard'></div></div>");
					console.log("Adding overlay");
					initGame();
					startedSnake = 1;
					document.onkeydown = enterKey;
				}
			}
		}
	}
}

document.onkeydown = checkKey;
