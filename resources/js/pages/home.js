// Home page demo of the "JS module + Datastar signals" pattern. Datastar owns the UI state (the
// `_tone` signals declared in templates/index.twig); this module owns what Datastar isn't built for,
// here a Web Audio tone. It reads signals inside effect() and writes results back with mergePatch().
// Signals starting with `_` stay in the browser: Datastar doesn't send them with backend requests.
import { effect, getPath, mergePatch } from 'datastar';
import { persist, ready } from 'starlite';

// Wait until Datastar has applied the page's data-signals before touching them.
await ready;

// The volume survives reloads; whether the tone is playing doesn't.
persist(['_tone.volume'], { key: 'starlite-demo' });

const AudioContext = window.AudioContext ?? window.webkitAudioContext;
if (!AudioContext) {
    mergePatch({ _tone: { supported: false } });
}

// Created on the first play: browsers only allow audio to start after a user gesture.
let audio = null;

effect(() => {
    const playing = getPath('_tone.playing');
    const volume = Number(getPath('_tone.volume'));
    if (!AudioContext || (!playing && audio === null)) {
        return;
    }
    audio ??= createTone(AudioContext);
    // A short ramp instead of a jump: no clicks when the slider moves.
    audio.gain.gain.setTargetAtTime(playing ? volume * 0.2 : 0, audio.context.currentTime, 0.05);
    if (playing) {
        audio.context.resume();
    }
});

function createTone(AudioContext) {
    const context = new AudioContext();
    const gain = new GainNode(context, { gain: 0 });
    const oscillator = new OscillatorNode(context, { type: 'sine', frequency: 220 });
    oscillator.connect(gain).connect(context.destination);
    oscillator.start();
    // Back to Datastar: the indicator shows whether the browser is really playing sound.
    context.addEventListener('statechange', () => mergePatch({ _tone: { running: context.state === 'running' } }));

    return { context, gain };
}
