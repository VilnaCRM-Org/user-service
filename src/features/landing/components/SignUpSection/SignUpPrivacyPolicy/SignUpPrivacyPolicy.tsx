import { Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import CustomCheckbox from '@/components/ui/CustomCheckbox/CustomCheckbox';
import CustomLink from '@/components/ui/CustomLink/CustomLink';
import { CHECKBOX_PRIVACY_POLICY_ID } from '@/features/landing/types/sign-up/types';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

interface ISignUpPrivacyPolicyProps {
  isCheckboxChecked: boolean;
  onPrivacyPolicyCheckboxChange: (checked: boolean) => void;
}

const styles = {
  typography: {
    color: '#404142',
    fontFamily: '"Inter-Regular", sans-serif',
    fontSize: '14px',
    fontStyle: 'normal',
    fontWeight: '500',
    lineHeight: '18px',
  },
};

export default function SignUpPrivacyPolicy({
  isCheckboxChecked,
  onPrivacyPolicyCheckboxChange,
}: ISignUpPrivacyPolicyProps) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const handleCheckboxChange = (checked: boolean) => {
    onPrivacyPolicyCheckboxChange(checked);
  };

  const checkboxLabelJSX = (
    <Typography style={{ ...styles.typography }}>
      {t('sign_up.form.confidential_text.firstText')}{' '}
      <CustomLink href="/" target="_blank">
        {t('sign_up.form.confidential_text.firstLink')}
      </CustomLink>{' '}
      {t('sign_up.form.confidential_text.secondText')}{' '}
      <CustomLink href="/" target="_blank">
        {t('sign_up.form.confidential_text.secondLink')}
      </CustomLink>{' '}
      {t('sign_up.form.confidential_text.thirdText')}
    </Typography>
  );

  return (
    <CustomCheckbox
      id={CHECKBOX_PRIVACY_POLICY_ID}
      checked={isCheckboxChecked}
      onChange={handleCheckboxChange}
    >
      {checkboxLabelJSX}
    </CustomCheckbox>
  );
}
