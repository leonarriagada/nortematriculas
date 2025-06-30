// js/script.js
// Inicializar todas las tablas ordenables cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initializeAllSortableTables);


document.addEventListener('DOMContentLoaded', function () {
    // Confirmación para eliminar registros
    const deleteLinks = document.querySelectorAll('a[href^="eliminar_estudiante.php"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            if (!confirm('¿Estás seguro de que quieres eliminar este registro?')) {
                e.preventDefault();
            }
        });
    });

    // Validación de formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Por favor, completa todos los campos requeridos.');
            }
        });
    });

    // Búsqueda en tiempo real (para la página de búsqueda de registros)
    const searchInput = document.querySelector('input[name="busqueda"]');
    if (searchInput) {
        let timeoutId;
        searchInput.addEventListener('input', function () {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                document.querySelector('form').submit();
            }, 500);
        });
    }

    // Mostrar/ocultar contraseña
    const passwordFields = document.querySelectorAll('input[type="password"]');
    passwordFields.forEach(field => {
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.textContent = 'Mostrar';
        toggleButton.classList.add('password-toggle');
        field.parentNode.insertBefore(toggleButton, field.nextSibling);

        toggleButton.addEventListener('click', function () {
            if (field.type === 'password') {
                field.type = 'text';
                this.textContent = 'Ocultar';
            } else {
                field.type = 'password';
                this.textContent = 'Mostrar';
            }
        });
    });

    // Cerrar mensajes de alerta automáticamente
    const alertMessages = document.querySelectorAll('.message');
    alertMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 300);
        }, 5000);
    });
});

function initializeSortableTable(tableId) {
    const table = document.getElementById(tableId);
    const headers = table.querySelectorAll('th');
    const tableBody = table.querySelector('tbody');
    const rows = tableBody.querySelectorAll('tr');
    const directions = Array.from(headers).map(() => '');

    const sortColumn = (index) => {
        const direction = directions[index] || 'asc';
        const multiplier = (direction === 'asc') ? 1 : -1;
        const newRows = Array.from(rows);

        newRows.sort((rowA, rowB) => {
            const cellA = rowA.querySelectorAll('td')[index].textContent.trim();
            const cellB = rowB.querySelectorAll('td')[index].textContent.trim();
            switch (true) {
                case cellA > cellB: return 1 * multiplier;
                case cellA < cellB: return -1 * multiplier;
                default: return 0;
            }
        });

        [].forEach.call(rows, (row) => {
            tableBody.removeChild(row);
        });

        newRows.forEach(newRow => tableBody.appendChild(newRow));

        directions[index] = direction === 'asc' ? 'desc' : 'asc';

        [].forEach.call(headers, (header, i) => {
            header.classList.remove('asc', 'desc');
            if (i === index) {
                header.classList.add(directions[index]);
            }
        });
    };

    [].forEach.call(headers, (header, index) => {
        header.addEventListener('click', () => {
            sortColumn(index);
        });
    });
}

// Función para inicializar todas las tablas ordenables en la página
function initializeAllSortableTables() {
    const tables = document.querySelectorAll('table[data-sortable]');
    tables.forEach(table => {
        initializeSortableTable(table.id);
    });
}

function descargarCSV(datos, nombreArchivo) {
    let csvContent = "data:text/csv;charset=utf-8,";

    // Agregar encabezados
    csvContent += "RUT,Nombre,Apellidos,Email\n";

    // Agregar datos
    datos.forEach(function (estudiante) {
        let row = `${estudiante.rut},${estudiante.nombre},${estudiante.apellido_p} ${estudiante.apellido_m},${estudiante.email}`;
        csvContent += row + "\n";
    });

    var encodedUri = encodeURI(csvContent);
    var link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", nombreArchivo);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}


function descargarCSV() {
    window.location.href = '<?php echo $_SERVER["PHP_SELF"]; ?>?anio=<?php echo $anio_filtro; ?>&trimestre=<?php echo $trimestre_filtro; ?>&descargar_csv=1';
}