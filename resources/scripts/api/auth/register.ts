import http from '@/api/http';

export interface RegisterResponse {
    success: boolean;
    message?: string;
    redirect?: string;
    error?: string;
}

export interface RegisterData {
    email: string;
    username: string;
    name_first: string;
    name_last: string;
    password: string;
    password_confirmation: string;
}

export default ({
    email,
    username,
    name_first,
    name_last,
    password,
    password_confirmation,
}: RegisterData): Promise<RegisterResponse> => {
    return new Promise((resolve, reject) => {
        http.get('/sanctum/csrf-cookie')
            .then(() =>
                http.post('/auth/register', {
                    email,
                    username,
                    name_first,
                    name_last,
                    password,
                    password_confirmation,
                })
            )
            .then((response) => {
                if (!(response.data instanceof Object)) {
                    return reject(new Error('An error occurred while processing the registration request.'));
                }

                return resolve({
                    success: response.data.success || false,
                    message: response.data.message,
                    redirect: response.data.redirect,
                    error: response.data.error,
                });
            })
            .catch(reject);
    });
};
