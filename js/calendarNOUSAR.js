/* document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar1');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: 'SCH_Calendar.php', // Asegúrate de que apunta al archivo correcto de PHP
        eventRender: function (info) {
            var tooltip = new Tooltip(info.el, {
                title: info.event.extendedProps.description,
                placement: 'top',
                trigger: 'hover',
                container: 'body'
            });
        }
    });

    calendar.render();
});



    document.addEventListener('DOMContentLoaded', function() {
        var url= 0;
        

        var calendarEl = document.getElementById('calendar1');
        var calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar:{left:"prev,next today",center:"title",right:"dayGridMonth,dayGridWeek,dayGridDay"},
        
        themeSystem: 'bootstrap5',defaultDate:"2022-03-12",
        navLinks:false,businessHours:true,editable: false,
        events: 
        {
            url: 'class/AX_Agenda.php'
        },
            textColor: 'white',
            eventDidMount: function(info) {
              if (info.event.extendedProps.status === 'done') {          
                // Change background color of row
                info.el.style.backgroundColor = 'green';
                info.el.style.textColor = 'white';
                // Change color of dot marker
                var dotEl = info.el.getElementsByClassName('fc-event-dot')[0];
                if (dotEl) {
                  dotEl.style.backgroundColor = 'white';
                }
              }
            }
        });
        calendar.render();  
    }); */
  