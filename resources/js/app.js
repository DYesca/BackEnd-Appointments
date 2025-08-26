import './bootstrap';

Echo.private('appointments')
    .listen('AppointmentStatusUpdated', (e) => {
        console.log(e.appointment);
    });
