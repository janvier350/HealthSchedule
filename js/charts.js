const labels = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
  ];



const data = {
    labels: labels,
    datasets: [{
        label: 'Concretado',
        data: [45, 30, 70, 42, 90, 0, 0],
        backgroundColor: [        
            'rgba(75, 192, 192, 0.4)'
        ],
        borderColor: [            
            'rgb(75, 192, 192)'
        ],
        borderWidth: 1
    },{
        label: 'Proceso',
        data: [45, 30, 70, 42, 90, 78, 10],
        backgroundColor: [        
            'rgba(255, 159, 64, 0.4)'
        ],
        borderColor: [            
            'rgb(255, 159, 64)'
        ],
        borderWidth: 1
    },{
        label: 'Negado',
        data: [65, 59, 80, 81, 56, 55, 40],
        backgroundColor: [
            'rgba(255, 99, 132, 0.4)'                       
        ],
        borderColor: [
            'rgb(255, 99, 132)'            
        ],
        borderWidth: 1
    }]
};

  const config = {
    type: 'bar',
    data: data,
    options: {
        scales: {
            y: {
              beginAtZero: true
            }
        }
    },
  };

