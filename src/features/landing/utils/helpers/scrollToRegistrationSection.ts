import { SIGN_UP_SECTION_ID } from '@/features/landing/utils/constants/constants';

export function scrollToRegistrationSection() {
  const registrationSection = document.getElementById(SIGN_UP_SECTION_ID);

  if (registrationSection) {
    registrationSection.scrollIntoView({ behavior: 'smooth' });
  }
}
