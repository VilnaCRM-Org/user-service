import { SIGN_UP_SECTION_ID } from '../constants/constants';

export default function scrollToRegistrationSection() {
  const registrationSection = document.getElementById(SIGN_UP_SECTION_ID);

  if (registrationSection) {
    registrationSection.scrollIntoView({ behavior: 'smooth' });
  }
}
