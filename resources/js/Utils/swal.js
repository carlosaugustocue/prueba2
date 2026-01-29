import Swal from 'sweetalert2';

const base = {
  buttonsStyling: false,
  customClass: {
    popup: 'rounded-2xl',
    title: 'text-gray-900',
    htmlContainer: 'text-gray-600',
    confirmButton:
      'inline-flex items-center justify-center px-4 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors',
    cancelButton:
      'inline-flex items-center justify-center px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors',
    actions: 'gap-2',
  },
};

export function toast({ title, icon = 'success' }) {
  return Swal.fire({
    ...base,
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    icon,
    title,
  });
}

export async function confirmDialog({
  title,
  text,
  icon = 'warning',
  confirmButtonText = 'Confirmar',
  cancelButtonText = 'Cancelar',
}) {
  const res = await Swal.fire({
    ...base,
    icon,
    title,
    text,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText,
    reverseButtons: true,
    focusCancel: true,
  });

  return res.isConfirmed;
}

export function alertDialog({ title, text, icon = 'info' }) {
  return Swal.fire({
    ...base,
    icon,
    title,
    text,
  });
}

