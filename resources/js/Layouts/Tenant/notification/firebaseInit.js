import firebase from "firebase/app";
import "firebase/messaging";

// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {

    apiKey: "AIzaSyBfxnNuTfu2ZSbYctadN5scjlo4SJll7Ds",
    authDomain: "flower-cloud-messaging.firebaseapp.com",
    projectId: "flower-cloud-messaging",
    storageBucket: "flower-cloud-messaging.appspot.com",
    messagingSenderId: "1011725193000",
    appId: "1:1011725193000:web:406a68caeb77c33c35ca1d",


    // databaseURL: "FROM FIREBASE CONSOLE",
    // measurementId: "FROM FIREBASE CONSOLE",
};

firebase.initializeApp(firebaseConfig);

const messaging = firebase.messaging();

const { REACT_APP_VAPID_KEY } = process.env;

const publicKey = REACT_APP_VAPID_KEY;

export const getToken = async (setTokenFound) => {
    let currentToken = "";

    try {
        currentToken = await messaging.getToken({ vapidKey: publicKey });
        if (currentToken) {
            setTokenFound(true);
        } else {
            setTokenFound(false);
        }
    } catch (error) {
        // eslint-disable-next-line no-console
        console.error("An error occurred while retrieving token.", error);
    }

    return currentToken;
};

export const onMessageListener = () =>
    new Promise((resolve) => {
        messaging.onMessage((payload) => {
            resolve(payload);
        });
    });
