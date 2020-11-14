$(document).ready(function() {
	clippy.load('Merlin', function(agent){
		agent.show();
		agent.speak('Willkommen im Vorlesungsverzeichnis der Philosophen!');
		agent.play("Greet");

		agent.speak('Ich werde versuchen, einen Zaubertrank zu brauen, damit dein Studium problemlos funktioniert.');
		agent.play("Process");

		agent.speak('Hmhmhmhmh... fertig, ja, aber wie funktioniert das nun genau..?');
		agent.play("Searching");

		agent.play("Ahhhhhh ich habs!");
		agent.play("Surprised");
		agent.play("Pleased");

		agent.speak('Am Besten funktioniert er, wenn du tatsächlich lernst und tatsächlich alle Übungen, Seminare usw. richtig mitmachst.');
		agent.play("Reading");

		agent.speak('Toll! Diesen Zaubertrank geb ich als Bachelor-Arbeit ab.');
		agent.play("Pleased");

		agent.play("Write");
		agent.play("WriteContinued");
		agent.play("WriteReturn");
		agent.play("Think");
		agent.play("Pleased");

		agent.play("Write");
		agent.play("WriteContinued");
		agent.play("WriteReturn");
		agent.play("Think");
		agent.play("Pleased");

		agent.play("Write");
		agent.play("WriteContinued");
		agent.play("WriteReturn");
		agent.play("Think");
		agent.play("Pleased");
	});
});
