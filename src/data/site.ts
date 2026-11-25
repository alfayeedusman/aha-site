// Facts about aha! shared by the page, structured data (JSON-LD), llms.txt and the press kit.
// Keep them true: search engines and AI assistants quote this text.
import { config } from '../config';

export const summary =
  'aha! - Where I Put It is an iPhone app that remembers where you put things. Save a spot with a photo and a pin, by voice or by typing, then ask "where\'s my passport?" and get the answer with the photo and the pin.';

export const longDescription = [
  summary,
  'It is made for the things you use rarely and lose often: passports, spare keys, birth certificates, chargers, seasonal decorations and boxes in storage. Places and boxes (closets, drawers, garage shelves) keep a whole home organized, and a location history shows every spot an item has lived.',
  `aha! is private by design. There is no account, no server, no ads and no tracking: everything stays on the iPhone. A Secret Vault keeps private memories behind Face ID, and backups are password-encrypted files the user keeps. It is a one-time purchase (${config.price.display}, local prices vary by country) with no subscription and no in-app purchases.`,
];

export const keyFacts: [string, string][] = [
  ['App', config.appName],
  ['Platform', `iPhone, iOS ${config.minIos} or later`],
  ['Price', `${config.price.display} once (App Store local pricing applies). No subscription, no in-app purchases, no ads.`],
  ['Category', 'Productivity, Lifestyle'],
  ['Language', 'English'],
  ['Availability', 'Worldwide on the App Store'],
  ['Privacy', 'No account, no server, no analytics in the app. Data stays on the device.'],
  ['Developer', `${config.companyName}, ${config.address}`],
  ['Contact', config.supportEmail],
];

export const features = [
  ['Visual Memory Pin', 'Tap the photo right where it lives. No more "it\'s in there somewhere."'],
  ['Remember a whole box', 'One photo, one place, and everything inside becomes searchable.'],
  ['Places and boxes', 'Your whole home, mapped: closets, drawers, garage shelves.'],
  ['Location history', "Every spot it's lived and when, plus a timeline of finds."],
  ['Just say it', 'On-device voice capture turns a sentence into a saved memory you confirm.'],
  ['Secret Vault', 'Some things are just for you. Private spots stay behind Face ID.'],
] as const;

export const faqs: [string, string][] = [
  ['What is aha!?', summary],
  ['How much does aha! cost?', `${config.price.display}, paid once. The App Store sets the local price in each country. There is no subscription, no in-app purchase and no ads.`],
  ['Do I need an account?', "No. Open the app and start remembering. There's nothing to sign up for."],
  ['Which iPhones does aha! work on?', `Any iPhone running iOS ${config.minIos} or later.`],
  ['Does aha! work offline?', 'Yes. Everything is stored and searched on your iPhone, so it works without an internet connection.'],
  ['Does aha! use AI or the cloud?', 'No. Search and the sentence you type or say are understood on your iPhone with simple on-device matching, and you always confirm before saving. Nothing is sent to a server.'],
  ['What happens if I lose my phone?', 'aha! has no cloud, so make a backup from Settings → Backup & restore. It creates a password-protected .aha file you can keep in Files, iCloud Drive or anywhere you like, and restore on a new iPhone.'],
  ['I forgot my backup password.', "We never see or store it, so it can't be reset. Create a new backup from the app with a password you'll remember."],
  ['Is voice processed on my phone?', 'Yes, when your iPhone supports on-device recognition. If it doesn\'t, aha! only uses Apple\'s online speech service if you turn on "Online voice fallback" in Settings. Otherwise you can type.'],
  ['How do I open Secret Vault?', "Go to the Memories tab and tap Secret Vault. It opens with Face ID, or your iPhone passcode if Face ID isn't available."],
  ['Is aha! available in my country?', 'aha! is offered worldwide on the App Store and works the same everywhere. The app is in English.'],
  ['How do I get a refund?', 'Purchases are handled by Apple. Request a refund at reportaproblem.apple.com.'],
];

export const video = {
  path: 'video/aha-launch-ad.mp4',
  poster: 'video/aha-launch-ad-poster.jpg',
  title: 'aha! - Where I Put It: launch video',
  description: 'In under a minute: save where you put something with a photo and a pin, then just ask where it is.',
  duration: 'PT54S',
  uploadDate: '2026-11-25',
  transcript: [
    'You put it somewhere safe.',
    "So safe, even you can't find it.",
    'Kitchen? Nope. Closet? Nope.',
    'The fridge? …Why would it be in the fridge?',
    'Meet aha! The app that remembers where you put things.',
    'Put something away? Point. Snap. Pin the exact spot.',
    'Done. Five seconds, tops.',
    "Later, just ask: where's my passport?",
    'Top cubby, hallway cabinet. Crisis averted.',
    'Spare key? Birth certificate? Christmas lights? Asked. Answered.',
    'Every place. Every box. Every move. All in one tidy app.',
    "Got secrets? There's a vault for that.",
    'aha! One less thing to remember.',
    'Private. On your iPhone. Go find out.',
  ],
};

export const screenshots = [
  ['01-home', 'Home: what are we finding today?'],
  ['02-ask', "Ask: where's my passport"],
  ['03-pin', 'Pin the exact spot on a photo'],
  ['04-found', 'Found it!'],
  ['05-places', 'Places and boxes'],
  ['06-history', 'Location history'],
  ['07-vault', 'Secret Vault'],
  ['08-privacy', 'Private by design'],
] as const;
