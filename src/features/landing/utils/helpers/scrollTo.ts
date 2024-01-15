export function scrollTo(id: string, offSet = 0) {
  const element = document.getElementById(id);

  const off = element?.offsetTop || 0;
  window.scrollTo({
    top: off - offSet,
    behavior: 'smooth',
  });
}
